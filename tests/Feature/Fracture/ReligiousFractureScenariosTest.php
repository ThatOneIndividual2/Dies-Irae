<?php

namespace Tests\Feature\Fracture;

use App\Actions\Heresy\BackSchismClaimant;
use App\Actions\Heresy\DetectReligiousMovement;
use App\Actions\Heresy\FoundReligiousMovement;
use App\Actions\Heresy\InfiltrateWithCult;
use App\Actions\Heresy\IssueChurchFractureResponse;
use App\Actions\Heresy\IssueSecularFractureResponse;
use App\Actions\Heresy\OpenAntipapalSchism;
use App\Actions\Heresy\PerformCultRitual;
use App\Actions\Heresy\PledgeSchismObedience;
use App\Actions\Heresy\PledgeSchismSee;
use App\Actions\Heresy\RecruitCultMember;
use App\Actions\Heresy\SpreadReligiousMovement;
use App\Domain\Enums\ChurchFractureResponse;
use App\Domain\Enums\DetectionSource;
use App\Domain\Enums\FractureKind;
use App\Domain\Enums\MovementVisibility;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\PapalClaimStatus;
use App\Domain\Enums\SecularFractureResponse;
use App\Domain\Enums\SpreadVector;
use App\Domain\Heresy\FractureException;
use App\Models\AntiClericalUnrest;
use App\Models\ApostasyState;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\ExcommunicationState;
use App\Models\Heresy;
use App\Models\ReligiousMovement;
use App\Models\SchismObedience;
use App\Models\SchismSeeAllegiance;
use App\Models\TerritoryAdjacency;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\ChurchWorldGraph;
use Tests\TestCase;

class ReligiousFractureScenariosTest extends TestCase
{
    use RefreshDatabase;

    private ChurchWorldGraph $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->g = ChurchWorldGraph::seed();
    }

    public function test_harmless_reform_movement_is_not_heresy(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-01');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::POPULAR_MOVEMENT,
            'flagellant-reform',
            'Penitential brotherhood',
            $g->priest,
            $g->parishLand,
            $date,
            ['orthodoxy_stance' => 'reform', 'fervor' => 30, 'founder_is_clergy' => true]
        );

        $this->assertSame(FractureKind::POPULAR_MOVEMENT, $movement->kind);
        $this->assertSame(MovementVisibility::PUBLIC, $movement->visibility);
        $this->assertNull($movement->heresy);
        $this->assertNotNull($movement->popularState);
        $this->assertSame('reform', $movement->popularState->orthodoxy_stance);
        $this->assertFalse($movement->popularState->church_regularized);

        app(IssueChurchFractureResponse::class)->execute(
            $g->priest,
            $movement,
            ChurchFractureResponse::PREACHING,
            $date
        );

        $this->assertLessThan(30, $movement->fresh()->popularState->fervor);

        $penance = app(IssueChurchFractureResponse::class)->execute(
            $g->bishop,
            $movement,
            ChurchFractureResponse::PENANCE,
            $date
        );
        $this->assertSame('regularized', $penance->result);
        $this->assertTrue($movement->fresh()->popularState->church_regularized);

        $this->expectException(FractureException::class);
        app(IssueChurchFractureResponse::class)->execute(
            $g->pope,
            $movement,
            ChurchFractureResponse::THEOLOGICAL_CONDEMNATION,
            $date
        );
    }

    public function test_doctrinal_heresy_has_named_error_and_can_be_condemned(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-02');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::DOCTRINAL_HERESY,
            'waldensian-error',
            'Denial of ordained preaching monopoly',
            $g->layCount,
            $g->paris,
            $date,
            ['doctrine_error_key' => 'lay_preaching', 'intensity' => 20]
        );

        $this->assertSame(FractureKind::DOCTRINAL_HERESY, $movement->kind);
        $this->assertSame(MovementVisibility::RUMORED, $movement->visibility);
        $this->assertNotNull($movement->heresy);
        $this->assertSame('named', $movement->heresy->judgement);
        $this->assertSame('lay_preaching', $movement->heresy->doctrine_error_key);
        $this->assertNull($movement->popularState);
        $this->assertNull($movement->cultOrganization);
        $this->assertNull($movement->schism);

        app(IssueChurchFractureResponse::class)->execute(
            $g->bishop,
            $movement,
            ChurchFractureResponse::THEOLOGICAL_CONDEMNATION,
            $date
        );

        $this->assertSame('condemned', $movement->fresh()->heresy->judgement);

        app(IssueChurchFractureResponse::class)->execute(
            $g->pope,
            $movement,
            ChurchFractureResponse::EXCOMMUNICATION,
            $date
        );

        $this->assertTrue(
            ExcommunicationState::query()
                ->where('character_id', $g->layCount->id)
                ->where('is_current', true)
                ->exists()
        );
    }

    public function test_clandestine_cult_stays_secret_until_true_detection(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-03');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::CLANDESTINE_CULT,
            'night-watchers',
            'Night Watchers',
            $g->monk,
            $g->parishLand,
            $date
        );

        $org = $movement->cultOrganization;
        $this->assertNotNull($org);
        $this->assertSame(FractureKind::CLANDESTINE_CULT, $org->kind);
        $this->assertNull($org->faction_id);
        $this->assertSame('hidden', $org->discovery_state);
        $this->assertSame(MovementVisibility::SECRET, $movement->visibility);
        $this->assertSame(1, $org->cells()->count());
        $this->assertFalse(Cult::query()->where('organization_id', $org->id)->exists());

        $rumor = app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::RUMOR,
            $g->deacon,
            $date
        );
        $this->assertSame('rumor', $rumor->outcome);
        $this->assertFalse($rumor->public_reveal);
        $this->assertSame(MovementVisibility::RUMORED, $movement->fresh()->visibility);

        $false = app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::FALSE_ACCUSATION,
            $g->layCount,
            $date,
            ['accused_character_id' => $g->monk->id]
        );
        $this->assertSame('false', $false->outcome);
        $this->assertFalse($false->public_reveal);
        $this->assertNotSame(MovementVisibility::PUBLIC, $movement->fresh()->visibility);

        $confession = app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::CONFESSION,
            $g->priest,
            $date
        );
        $this->assertTrue($confession->sealed_confession);
        $this->assertFalse($confession->public_reveal);

        $this->expectException(FractureException::class);
        app(PerformCultRitual::class)->execute($org, 'blood-pact', $date, true);
    }

    public function test_demon_cult_has_patronage_sacrifice_and_overlay(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-04');

        $faction = DemonicFaction::query()->create([
            'world_id' => $g->world->id,
            'key' => 'belial',
            'name' => 'Belial',
        ]);

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::DEMONIC_CULT,
            'belial-cell',
            'Servants of Belial',
            $g->monk,
            $g->paris,
            $date,
            [
                'faction_id' => $faction->id,
                'hidden_objective' => 'open_a_breach',
                'objective_key' => 'open_a_breach',
            ]
        );

        $org = $movement->cultOrganization->fresh();
        $this->assertSame(FractureKind::DEMONIC_CULT, $org->kind);
        $this->assertSame($faction->id, $org->faction_id);
        $this->assertSame('open_a_breach', $org->hidden_objective);
        $this->assertTrue($org->objectives()->where('objective_key', 'open_a_breach')->exists());

        $overlay = Cult::query()->where('organization_id', $org->id)->first();
        $this->assertNotNull($overlay);
        $this->assertSame($faction->id, $overlay->faction_id);
        $this->assertSame('hidden', $overlay->discovery_state);

        $rite = app(PerformCultRitual::class)->execute($org, 'blood-pact', $date, true, 8);
        $this->assertTrue($rite->requires_sacrifice);

        app(RecruitCultMember::class)->execute($org, $g->deacon, $date, $org->cells()->first(), 'initiate');
        $infiltration = app(InfiltrateWithCult::class)->execute($org, $g->deacon, 'see', $date, $g->diocese->id);
        $this->assertSame('see', $infiltration->target_type);
        $this->assertTrue($org->fresh()->infiltrating_church);

        app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::INQUISITORIAL_INVESTIGATION,
            $g->bishop,
            $date
        );
        $this->assertSame(MovementVisibility::PUBLIC, $movement->fresh()->visibility);
        $this->assertSame('revealed', $org->fresh()->discovery_state);
    }

    public function test_antipapal_schism_builds_parallel_obedience(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-05');

        $schism = app(OpenAntipapalSchism::class)->execute(
            $g->antipope,
            $g->papacy->fresh(),
            $date,
            null,
            'western-split'
        );

        $this->assertSame('open', $schism->status);
        $this->assertSame(FractureKind::SCHISM, $schism->movement->kind);
        $this->assertSame(MovementVisibility::PUBLIC, $schism->movement->visibility);
        $this->assertSame(PapacyStatus::SCHISM, $g->papacy->fresh()->status);
        $this->assertSame(PapalClaimStatus::ANTIPOPE, $schism->rivalClaim->status);
        $this->assertSame(PapalClaimStatus::RECOGNIZED, $schism->recognizedClaim->status);

        $this->assertTrue(
            SchismObedience::query()
                ->where('schism_id', $schism->id)
                ->where('character_id', $g->pope->id)
                ->where('papal_claim_id', $schism->recognized_claim_id)
                ->where('is_current', true)
                ->exists()
        );

        app(PledgeSchismObedience::class)->execute($schism, $g->bishop, $schism->rivalClaim, $date);
        app(PledgeSchismSee::class)->execute($schism, $g->diocese, $schism->rivalClaim, $date);
        app(BackSchismClaimant::class)->execute($schism, $g->king, $schism->rivalClaim, $date);

        $this->assertTrue(
            SchismSeeAllegiance::query()
                ->where('see_id', $g->diocese->id)
                ->where('papal_claim_id', $schism->rival_claim_id)
                ->where('is_current', true)
                ->exists()
        );
        $this->assertTrue($schism->secularBackers()->where('character_id', $g->king->id)->where('is_current', true)->exists());
        $this->assertNull($schism->movement->heresy);
    }

    public function test_ruler_backing_heresy_patronizes_without_becoming_clergy(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-06');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::DOCTRINAL_HERESY,
            'royal-error',
            'Royal chaplaincy error',
            $g->priest,
            $g->paris,
            $date,
            ['doctrine_error_key' => 'royal_supremacy', 'founder_is_clergy' => true]
        );

        $record = app(IssueSecularFractureResponse::class)->execute(
            $g->king,
            $movement,
            SecularFractureResponse::PATRONIZE,
            $date
        );

        $this->assertSame('patronized', $record->result);
        $this->assertTrue(
            $movement->adherents()->where('character_id', $g->king->id)->where('is_patron', true)->exists()
        );
        $this->assertFalse($g->king->currentSpiritualHolderships()->exists());
        $this->assertSame(MovementVisibility::PUBLIC, $movement->fresh()->visibility);

        TerritoryAdjacency::query()->create([
            'world_id' => $g->world->id,
            'from_territory_id' => $g->paris->id,
            'to_territory_id' => $g->reims->id,
        ]);

        $presence = app(SpreadReligiousMovement::class)->execute(
            $movement,
            $g->paris,
            $g->reims,
            SpreadVector::NOBLE_PATRONAGE,
            $g->king,
            $date
        );
        $this->assertSame($g->reims->id, $presence->territory_id);
    }

    public function test_successful_reconciliation_closes_heresy_without_unrest(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-07');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::DOCTRINAL_HERESY,
            'penitent-error',
            'Retracted error',
            $g->layCount,
            $g->paris,
            $date,
            ['doctrine_error_key' => 'eucharistic_error', 'intensity' => 8]
        );

        $record = app(IssueChurchFractureResponse::class)->execute(
            $g->bishop,
            $movement,
            ChurchFractureResponse::RECONCILIATION,
            $date
        );

        $this->assertSame('heresy_reconciled', $record->result);
        $this->assertSame('reconciled', $movement->fresh()->status);
        $this->assertSame('reconciled', $movement->fresh()->heresy->judgement);
        $this->assertSame(0, AntiClericalUnrest::query()->where('world_id', $g->world->id)->count());
    }

    public function test_failed_suppression_radicalizes_and_spawns_unrest(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-08');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::POPULAR_MOVEMENT,
            'bread-riots-piety',
            'Grain psalmists',
            $g->layCount,
            $g->paris,
            $date,
            ['fervor' => 40, 'intensity' => 50]
        );

        $record = app(IssueSecularFractureResponse::class)->execute(
            $g->king,
            $movement,
            SecularFractureResponse::SUPPRESS,
            $date
        );

        $this->assertSame('failed_radicalized', $record->result);
        $this->assertSame(25, $record->radicalization_delta);
        $movement = $movement->fresh();
        $this->assertSame(25, $movement->radicalization);
        $this->assertSame('radicalized', $movement->popularState->orthodoxy_stance);
        $this->assertGreaterThan(0, $movement->popularState->violence);
        $this->assertSame('active', $movement->status);

        $unrest = ReligiousMovement::query()
            ->where('world_id', $g->world->id)
            ->where('kind', FractureKind::ANTI_CLERICAL_UNREST)
            ->first();
        $this->assertNotNull($unrest);
        $this->assertNotNull($unrest->antiClericalUnrest);
        $this->assertNull($unrest->heresy);
    }

    public function test_apostasy_false_prophet_and_unrest_are_distinct_ledgers(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-09');

        $apostasy = app(FoundReligiousMovement::class)->execute(
            FractureKind::APOSTASY,
            'renunciation',
            'Renunciation of baptism',
            $g->layCount,
            $g->paris,
            $date,
            ['from_faith_key' => 'latin_christianity', 'cause' => 'renunciation']
        );
        $this->assertNotNull($apostasy->apostasyState);
        $this->assertNull($apostasy->heresy);
        $this->assertTrue(ApostasyState::query()->where('character_id', $g->layCount->id)->where('is_current', true)->exists());

        $prophet = app(FoundReligiousMovement::class)->execute(
            FractureKind::FALSE_PROPHET_MOVEMENT,
            'new-era',
            'New era visions',
            $g->monk,
            $g->parishLand,
            $date,
            ['claimed_revelation' => 'private vision']
        );
        $this->assertNotNull($prophet->falseProphetState);
        $this->assertSame('unexamined', $prophet->falseProphetState->examination);
        $this->assertNull($prophet->heresy);

        app(IssueChurchFractureResponse::class)->execute(
            $g->bishop,
            $prophet,
            ChurchFractureResponse::LOCAL_SYNOD,
            $date
        );
        $this->assertSame('examined', $prophet->fresh()->falseProphetState->examination);

        app(IssueChurchFractureResponse::class)->execute(
            $g->pope,
            $prophet,
            ChurchFractureResponse::THEOLOGICAL_CONDEMNATION,
            $date
        );
        $this->assertSame('condemned', $prophet->fresh()->falseProphetState->examination);
        $this->assertSame('condemned', Heresy::query()->where('movement_id', $prophet->id)->value('judgement'));

        $unrest = app(FoundReligiousMovement::class)->execute(
            FractureKind::ANTI_CLERICAL_UNREST,
            'tax-riots',
            'Tithe riots',
            $g->layCount,
            $g->reims,
            $date,
            ['intensity' => 40]
        );
        $this->assertNotNull($unrest->antiClericalUnrest);
        $this->assertNull($unrest->heresy);
        $this->assertNull($unrest->cultOrganization);

        $this->expectException(FractureException::class);
        app(IssueChurchFractureResponse::class)->execute(
            $g->pope,
            $unrest,
            ChurchFractureResponse::THEOLOGICAL_CONDEMNATION,
            $date
        );
    }

    public function test_secret_cult_cannot_preach_and_clergy_spread_requires_clergy(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-10');

        $cult = app(FoundReligiousMovement::class)->execute(
            FractureKind::CLANDESTINE_CULT,
            'silent-cell',
            'Silent cell',
            $g->monk,
            $g->paris,
            $date
        );

        try {
            app(SpreadReligiousMovement::class)->execute(
                $cult,
                $g->paris,
                $g->reims,
                SpreadVector::PREACHING,
                $g->monk,
                $date
            );
            $this->fail('Secret cult preaching should be rejected.');
        } catch (FractureException $e) {
            $this->assertStringContainsString('preaching', $e->getMessage());
        }

        $heresy = app(FoundReligiousMovement::class)->execute(
            FractureKind::DOCTRINAL_HERESY,
            'clerical-error',
            'Clerical error',
            $g->priest,
            $g->paris,
            $date,
            ['founder_is_clergy' => true, 'doctrine_error_key' => 'simony_denial']
        );

        $presence = app(SpreadReligiousMovement::class)->execute(
            $heresy,
            $g->paris,
            $g->reims,
            SpreadVector::CLERGY,
            $g->priest,
            $date
        );
        $this->assertSame($g->reims->id, $presence->territory_id);
        $this->assertTrue($heresy->spreadEvents()->where('vector', SpreadVector::CLERGY)->exists());
    }

    public function test_informants_and_denunciations_do_not_publicly_reveal(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-11');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::CLANDESTINE_CULT,
            'hidden-choir',
            'Hidden choir',
            $g->monk,
            $g->parishLand,
            $date
        );

        $denunciation = app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::DENUNCIATION,
            $g->layCount,
            $date
        );
        $informant = app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::INFORMANT,
            $g->deacon,
            $date
        );

        $this->assertSame('confirmed', $denunciation->outcome);
        $this->assertFalse($denunciation->public_reveal);
        $this->assertFalse($informant->public_reveal);
        $this->assertSame(MovementVisibility::SECRET, $movement->fresh()->visibility);

        app(DetectReligiousMovement::class)->execute(
            $movement,
            DetectionSource::CLERGY_REPORT,
            $g->priest,
            $date
        );
        $this->assertSame(MovementVisibility::PUBLIC, $movement->fresh()->visibility);
    }

    public function test_reform_cannot_be_reconciled_as_heresy(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-12');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::POPULAR_MOVEMENT,
            'bequinage',
            'Beguine houses',
            $g->priest,
            $g->paris,
            $date,
            ['founder_is_clergy' => true]
        );

        $this->expectException(FractureException::class);
        app(IssueChurchFractureResponse::class)->execute(
            $g->bishop,
            $movement,
            ChurchFractureResponse::RECONCILIATION,
            $date
        );
    }

    public function test_failed_cult_suppression_deepens_secrecy(): void
    {
        $g = $this->g;
        $date = Carbon::parse('1348-07-13');

        $movement = app(FoundReligiousMovement::class)->execute(
            FractureKind::CLANDESTINE_CULT,
            'deep-cell',
            'Deep cell',
            $g->monk,
            $g->paris,
            $date,
            ['intensity' => 40, 'secrecy' => 60]
        );
        $before = (int) $movement->cultOrganization->secrecy;

        $record = app(IssueSecularFractureResponse::class)->execute(
            $g->king,
            $movement,
            SecularFractureResponse::SUPPRESS,
            $date
        );

        $this->assertSame('failed_radicalized', $record->result);
        $org = $movement->fresh()->cultOrganization;
        $this->assertGreaterThan($before, $org->secrecy);
        $this->assertSame('hidden', $org->discovery_state);
        $this->assertSame(MovementVisibility::SECRET, $movement->fresh()->visibility);
    }
}
