<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Campaign\ResolveGameEvent;
use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\Succession\PreviewSuccession;
use App\Actions\Time\AdvanceWorldCalendar;
use App\Domain\Campaign\BeatKey;
use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Enums\OverlayState;
use App\Domain\Enums\SacramentType;
use App\Models\Character;
use App\Models\CountermeasureAttemptRecord;
use App\Models\GameEvent;
use App\Models\HellDailyPulse;
use App\Models\PlagueWave;
use App\Models\SacramentRecord;
use App\Models\Settlement;
use App\Models\SettlementPlagueState;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryIncursion;
use App\Models\TitleOwnership;
use App\Models\VassalRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountyContinuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_salon_names_a_living_heir_under_the_county_law(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->assertNotNull($slice->heir);
        $this->assertSame('hugues_adhemar', $slice->heir->key);
        $this->assertSame($slice->ruler->id, $slice->heir->father_id);
        $this->assertTrue($slice->heir->is_alive);

        $preview = app(PreviewSuccession::class)->execute($slice->countyTitle->fresh('successionLaw'), $slice->ruler);

        $this->assertSame('heir', $preview['outcome']);
        $this->assertSame($slice->heir->id, $preview['heir']->id);
        $this->assertSame('male_preference_primogeniture', $preview['law_key']);
    }

    public function test_aix_wave_seeds_settlement_plague_and_a_later_day_kills(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->openAndResolve($slice->world->id, BeatKey::PLAGUE_APPEARS, 'send_physicians');

        $this->assertSame(1, PlagueWave::query()->where('world_id', $slice->world->id)->count());
        $aix = Settlement::query()->where('world_id', $slice->world->id)->where('settlement_key', 'aix')->firstOrFail();
        $focus = SettlementPlagueState::query()->where('settlement_id', $aix->id)->firstOrFail();
        $this->assertGreaterThan(0, (int) $focus->infectious + (int) $focus->incubating);

        $before = (int) Territory::query()->where('world_id', $slice->world->id)->where('key', 'aix')->value('population');
        app(AdvanceWorldCalendar::class)->execute($slice->world->fresh(), 6);
        $focus->refresh();
        $after = (int) Territory::query()->where('world_id', $slice->world->id)->where('key', 'aix')->value('population');

        $this->assertGreaterThan(0, (int) $focus->dead);
        $this->assertLessThan($before, $after);
        $this->assertGreaterThanOrEqual(0, $after);
    }

    public function test_abbey_grant_records_a_mass_for_the_dead(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->openAndResolve($slice->world->id, BeatKey::MONASTERY_REQUESTS_RESOURCES, 'grant');

        $mass = SacramentRecord::query()
            ->where('world_id', $slice->world->id)
            ->where('sacrament_type', SacramentType::EUCHARIST)
            ->firstOrFail();
        $this->assertSame($slice->ruler->id, $mass->subject_character_id);
        $this->assertSame($slice->abbot->id, $mass->minister_character_id);
        $this->assertContains($mass->validity, ['valid', 'valid_illicit']);
        $this->assertSame('mass_for_the_dead', $mass->metadata['rite'] ?? null);
        $this->assertTrue(
            SacramentRecord::query()
                ->where('subject_character_id', $slice->ruler->id)
                ->where('sacrament_type', SacramentType::BAPTISM)
                ->exists()
        );
    }

    public function test_exorcism_is_a_countermeasure_and_the_overlay_can_deepen(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->openAndResolve($slice->world->id, BeatKey::DEMONIC_MANIFESTATION, 'call_clergy');
        $this->openAndResolve($slice->world->id, BeatKey::CLERGY_RESPONSE, 'exorcism');

        $rite = CountermeasureAttemptRecord::query()->where('world_id', $slice->world->id)->firstOrFail();
        $this->assertSame('exorcism', $rite->kind);
        $this->assertContains($rite->result, ['success', 'partial', 'failure', 'backlash']);
        $this->assertSame($slice->bishop->id, $rite->actor_character_id);

        $aix = Territory::query()->where('world_id', $slice->world->id)->where('key', 'aix')->firstOrFail();
        app(AdvanceWorldCalendar::class)->execute($slice->world->fresh(), 1);

        $overlay = SupernaturalOverlay::query()->where('territory_id', $aix->id)->where('is_current', true)->firstOrFail();
        $this->assertSame(OverlayState::HAUNTED, $overlay->kind);
        $this->assertGreaterThan(0, (int) $overlay->intensity);

        $incursion = TerritoryIncursion::query()->where('territory_id', $aix->id)->where('is_current', true)->firstOrFail();
        $this->assertSame('manifested', $incursion->state);
        $this->assertGreaterThan(0, (int) $incursion->local_manifestation);
        $this->assertTrue(HellDailyPulse::query()->where('world_id', $slice->world->id)->exists());

        $holder = TitleOwnership::query()
            ->where('title_id', $slice->countyTitle->id)
            ->where('is_current', true)
            ->value('holder_character_id');
        $this->assertSame($slice->ruler->id, (int) $holder);
    }

    public function test_yielding_the_county_buries_raimond_and_seats_hugues(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);

        $event = GameEvent::query()->where('world_id', $slice->world->id)->where('event_key', 'count_stricken')->firstOrFail();
        $event->status = GameEventStatus::AWAITING_DECISION;
        $event->save();

        $this->post('/events/'.$event->id.'/resolve', ['option' => 'yield_the_county'])
            ->assertRedirect('/events');

        $raimond = Character::query()->whereKey($slice->ruler->id)->firstOrFail();
        $hugues = Character::query()->whereKey($slice->heir->id)->firstOrFail();
        $this->assertFalse($raimond->is_alive);
        $this->assertTrue($hugues->is_alive);
        $this->assertSame($hugues->id, (int) $slice->user->fresh()->controlled_character_id);

        $ownership = TitleOwnership::query()
            ->where('title_id', $slice->countyTitle->id)
            ->where('is_current', true)
            ->firstOrFail();
        $this->assertSame($hugues->id, (int) $ownership->holder_character_id);
        $this->assertSame(AcquisitionType::SUCCESSION, $ownership->acquisition_type);

        $closed = TitleOwnership::query()
            ->where('title_id', $slice->countyTitle->id)
            ->where('holder_character_id', $raimond->id)
            ->firstOrFail();
        $this->assertNotNull($closed->lost_date);

        $liege = VassalRelationship::query()
            ->where('vassal_character_id', $slice->vassal->id)
            ->where('is_current', true)
            ->firstOrFail();
        $this->assertSame($hugues->id, (int) $liege->liege_character_id);

        $this->get('/character')->assertOk()->assertSee('Hugues', false);
        $this->get('/titles')->assertOk()->assertSee('inherited', false);
    }

    private function openAndResolve(int $worldId, string $key, string $option): void
    {
        $event = GameEvent::query()->where('world_id', $worldId)->where('event_key', $key)->firstOrFail();
        $event->status = GameEventStatus::AWAITING_DECISION;
        $event->save();
        app(ResolveGameEvent::class)->execute($event->fresh(), $option);
    }
}
