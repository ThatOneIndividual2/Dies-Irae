<?php

namespace Tests\Feature\Church;

use App\Actions\Church\AuthorizeExtraordinarySpiritualAction;
use App\Actions\Church\ClaimPapacy;
use App\Actions\Church\DeclareHeresy;
use App\Actions\Church\DeclarePapalVacancy;
use App\Actions\Church\ExcommunicateCharacter;
use App\Actions\Church\GrantInvestitureRight;
use App\Actions\Church\LegitimizeExorcist;
use App\Actions\Church\LiftExcommunication;
use App\Actions\Church\LiftInterdict;
use App\Actions\Church\PlaceInterdict;
use App\Actions\Church\RecognizeRelicAuthenticity;
use App\Actions\Church\RecognizeSaint;
use App\Actions\Church\SanctionHolyOrder;
use App\Domain\Church\AppointmentPolicy;
use App\Domain\Church\ChurchAuthority;
use App\Domain\Church\ChurchException;
use App\Domain\Church\ClergyLegitimacy;
use App\Domain\Church\SacramentalAuthorityResolver;
use App\Domain\Enums\AppointmentMode;
use App\Domain\Enums\AppointmentStatus;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\PapalClaimStatus;
use App\Domain\Enums\RelicAuthenticity;
use App\Domain\Enums\SacramentKind;
use App\Domain\Enums\SpiritualActionKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Models\ClergyAppointment;
use App\Models\ExcommunicationState;
use App\Models\Papacy;
use App\Models\Relic;
use App\Models\SpiritualOffice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\ChurchWorldGraph;
use Tests\TestCase;

class ChurchPowersAndAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pope_can_excommunicate_a_king_and_lift_it(): void
    {
        $g = ChurchWorldGraph::seed();
        $church = app(ChurchAuthority::class);

        $state = app(ExcommunicateCharacter::class)->execute(
            $g->pope,
            $g->king,
            Carbon::parse('1348-07-01'),
            'defiance of the see'
        );

        $this->assertTrue($state->is_current);
        $this->assertTrue(app(ClergyLegitimacy::class)->isExcommunicated($g->king));
        $this->assertTrue($g->king->currentTitleOwnerships()->exists());

        app(LiftExcommunication::class)->execute($g->pope, $g->king, Carbon::parse('1348-08-01'));
        $this->assertFalse(app(ClergyLegitimacy::class)->isExcommunicated($g->king->fresh()));
        $this->assertSame(1, ExcommunicationState::query()->where('character_id', $g->king->id)->count());
    }

    public function test_king_cannot_excommunicate(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->expectException(ChurchException::class);
        app(ExcommunicateCharacter::class)->execute($g->king, $g->layCount, $g->date);
    }

    public function test_interdict_targets_a_see_not_a_realm(): void
    {
        $g = ChurchWorldGraph::seed();

        $state = app(PlaceInterdict::class)->execute(
            $g->pope,
            Carbon::parse('1348-07-02'),
            $g->diocese,
            null,
            'sacrilege in Paris'
        );

        $this->assertSame('see', $state->target_type);
        $this->assertSame($g->diocese->id, $state->see_id);
        $this->assertNull($state->territory_id);

        app(LiftInterdict::class)->execute($g->pope, $state, Carbon::parse('1348-07-10'));
        $this->assertNull($state->fresh()->is_current);
    }

    public function test_appointment_policy_is_data_driven_and_investiture_can_override(): void
    {
        $g = ChurchWorldGraph::seed();
        $policy = app(AppointmentPolicy::class);

        $this->assertSame(AppointmentMode::PAPAL, $policy->modeFor($g->bishopOffice));
        $this->assertSame(AppointmentMode::LOCAL, $policy->modeFor($g->priestOffice));
        $this->assertSame(AppointmentMode::ABBATIAL_ELECTION, $policy->modeFor($g->abbotOffice));

        app(GrantInvestitureRight::class)->execute(
            $g->diocese,
            AppointmentMode::LAY_INVESTITURE,
            Carbon::parse('1348-07-01'),
            $g->king,
            $g->kingdom
        );

        $this->assertSame(AppointmentMode::LAY_INVESTITURE, $policy->modeFor($g->bishopOffice->fresh()));
        $this->assertTrue($policy->actorMayAppoint($g->king, $g->bishopOffice->fresh(), AppointmentMode::LAY_INVESTITURE));
    }

    public function test_disputed_appointment_does_not_seat_either_claimant(): void
    {
        $g = ChurchWorldGraph::seed();
        app(\App\Actions\Church\RemoveFromSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            Carbon::parse('1348-07-01')
        );

        [$a, $b] = app(ChurchAuthority::class)->recordDisputedAppointment(
            $g->pope,
            $g->king,
            $g->bishopOffice->fresh(),
            $g->archbishop,
            $g->princeBishop,
            Carbon::parse('1348-07-02')
        );

        $this->assertSame(AppointmentStatus::DISPUTED, $a->status);
        $this->assertSame($a->id, $b->competing_appointment_id);
        $this->assertFalse(
            \App\Models\SpiritualOfficeHoldership::query()
                ->where('spiritual_office_id', $g->bishopOffice->id)
                ->where('is_current', true)
                ->exists()
        );
        $this->assertTrue(app(AppointmentPolicy::class)->isVacant($g->bishopOffice->fresh()));
    }

    public function test_vacancy_and_antipope_do_not_replace_the_papal_institution(): void
    {
        $g = ChurchWorldGraph::seed();
        $papacyId = $g->papacy->id;

        app(DeclarePapalVacancy::class)->execute($g->papacy, Carbon::parse('1348-09-01'));
        $g->papacy->refresh();
        $this->assertSame(PapacyStatus::VACANT, $g->papacy->status);
        $this->assertSame($papacyId, $g->papacy->id);
        $this->assertTrue(app(AppointmentPolicy::class)->isVacant($g->papalOffice->fresh()));

        $antipopeOffice = app(\App\Actions\Church\CreateSpiritualOffice::class)->execute(
            $g->world,
            'antipope-office',
            'Schismatic papal claimant',
            SpiritualOfficeRank::POPE
        );

        $claim = app(ClaimPapacy::class)->execute(
            $g->antipope,
            $g->papacy,
            Carbon::parse('1348-09-02'),
            PapalClaimStatus::ANTIPOPE,
            $antipopeOffice
        );

        $this->assertSame(PapalClaimStatus::ANTIPOPE, $claim->status);
        $this->assertSame($papacyId, Papacy::query()->where('world_id', $g->world->id)->value('id'));
        $this->assertNotSame($antipopeOffice->id, $g->papacy->fresh()->papal_office_id);
        $this->assertContains($g->papacy->fresh()->status, [PapacyStatus::DISPUTED, PapacyStatus::SCHISM]);
    }

    public function test_pope_recognizes_saints_relics_orders_heresy_exorcists_and_extraordinary_acts(): void
    {
        $g = ChurchWorldGraph::seed();

        $saint = app(RecognizeSaint::class)->execute(
            $g->pope,
            'st-denis-martyr',
            'Denis',
            Carbon::parse('1348-07-03')
        );
        $this->assertSame('canonized', $saint->recognition_status);

        $relic = Relic::query()->create([
            'world_id' => $g->world->id,
            'saint_id' => $saint->id,
            'key' => 'denis-head',
            'name' => 'Head of Denis',
            'authenticity' => RelicAuthenticity::UNRECOGNIZED,
            'current_holding_id' => $g->abbeyHolding->id,
        ]);
        $relic = app(RecognizeRelicAuthenticity::class)->execute(
            $g->pope,
            $relic,
            RelicAuthenticity::RECOGNIZED,
            Carbon::parse('1348-07-04')
        );
        $this->assertSame(RelicAuthenticity::RECOGNIZED, $relic->authenticity);

        $order = app(SanctionHolyOrder::class)->execute($g->pope, $g->templars, Carbon::parse('1348-07-05'));
        $this->assertSame('sanctioned', $order->sanction_status);
        $this->assertTrue($order->papal_protection);

        $heresy = app(DeclareHeresy::class)->execute($g->pope, 'flagellants', 'Flagellants', Carbon::parse('1348-07-06'));
        $this->assertSame('condemned', $heresy->judgement);

        $legit = app(LegitimizeExorcist::class)->execute($g->pope, $g->priest, Carbon::parse('1348-07-07'));
        $this->assertTrue($legit->is_current);
        $this->assertSame($g->priest->id, $legit->character_id);

        $auth = app(AuthorizeExtraordinarySpiritualAction::class)->execute(
            $g->pope,
            $g->priest,
            SpiritualActionKind::EXORCISM,
            Carbon::parse('1348-07-08')
        );
        $this->assertSame(SpiritualActionKind::EXORCISM, $auth->action_kind);
    }

    public function test_sacramental_authority_follows_orders_grade_not_secular_rank(): void
    {
        $g = ChurchWorldGraph::seed();
        $resolver = app(SacramentalAuthorityResolver::class);

        $this->assertTrue($resolver->canPerform($g->priest, SacramentKind::EUCHARIST));
        $this->assertFalse($resolver->canPerform($g->priest, SacramentKind::ORDERS));
        $this->assertTrue($resolver->canPerform($g->bishop, SacramentKind::ORDERS));
        $this->assertFalse($resolver->canPerform($g->king, SacramentKind::EUCHARIST));
        $this->assertTrue($resolver->canPerform($g->deacon, SacramentKind::BAPTISM));
        $this->assertFalse($resolver->canPerform($g->deacon, SacramentKind::EUCHARIST));
    }

    public function test_excommunicated_clergy_lose_sacramental_authority_but_keep_office_history(): void
    {
        $g = ChurchWorldGraph::seed();
        app(ExcommunicateCharacter::class)->execute($g->pope, $g->bishop, Carbon::parse('1348-07-09'));

        $this->assertFalse(app(SacramentalAuthorityResolver::class)->canPerform($g->bishop, SacramentKind::ORDERS));
        $this->assertTrue($g->bishop->currentSpiritualHolderships()->exists());
    }

    public function test_local_priest_cannot_appoint_a_bishop(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->expectException(ChurchException::class);
        app(\App\Actions\Church\AppointToSpiritualOffice::class)->execute(
            $g->priest,
            $g->bishopOffice,
            $g->princeBishop,
            Carbon::parse('1348-07-10')
        );
    }

    public function test_cardinal_and_deacon_offices_exist_beside_diocesan_ranks(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertSame(SpiritualOfficeRank::CARDINAL, $g->cardinalOffice->rank);
        $this->assertSame(SpiritualOfficeRank::DEACON, $g->deaconOffice->rank);
        $this->assertSame(SpiritualOfficeRank::ABBOT, $g->abbotOffice->rank);
        $this->assertNull($g->abbotOffice->see_id);
        $this->assertSame($g->monastery->id, $g->abbotOffice->monastery_id);
    }

    public function test_appointment_rows_are_recorded_when_pope_provisions_a_see(): void
    {
        $g = ChurchWorldGraph::seed();
        app(\App\Actions\Church\RemoveFromSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            Carbon::parse('1348-07-11')
        );

        $appointment = app(\App\Actions\Church\AppointToSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice->fresh(),
            $g->princeBishop,
            Carbon::parse('1348-07-12')
        );

        $this->assertSame(AppointmentMode::PAPAL, $appointment->mode);
        $this->assertSame(AppointmentStatus::RECOGNIZED, $appointment->status);
        $this->assertSame(1, ClergyAppointment::query()->where('spiritual_office_id', $g->bishopOffice->id)->count());
    }
}
