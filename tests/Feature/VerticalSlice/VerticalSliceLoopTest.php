<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Army\MoveArmy;
use App\Actions\Army\RaiseArmy;
use App\Actions\Army\ResolveBattle;
use App\Actions\Campaign\ResolveGameEvent;
use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\Time\AdvanceWorldCalendar;
use App\Domain\Campaign\BeatKey;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Models\Army;
use App\Models\Battle;
use App\Models\ChurchRelation;
use App\Models\CorruptionState;
use App\Models\Cult;
use App\Models\GameEvent;
use App\Models\HeresyPresence;
use App\Models\PlagueWave;
use App\Models\SpiritualOffice;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\Title;
use App\Validation\WorldIntegrityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerticalSliceLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_provence_loop_from_seed_to_aftermath(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $world = $slice->world;
        $salonStart = (int) $slice->territory('salon')->population;
        $aixStart = (int) $slice->territory('aix')->population;
        $standingStart = (int) ChurchRelation::query()->where('character_id', $slice->ruler->id)->value('standing');

        $this->assertSame('provence-1347', $world->slug);
        $this->assertSame('Raimond Adhémar', $slice->ruler->displayName());
        $this->assertNotNull($slice->dynasty);
        $this->assertNotNull($slice->countyTitle);
        $this->assertNotNull($slice->realm);
        $this->assertNotNull($slice->vassal);
        $this->assertNotNull($slice->see);
        $this->assertNotNull($slice->monastery);
        $this->assertNotNull($slice->papacy);
        $this->assertSame(0, PlagueWave::query()->where('world_id', $world->id)->count());

        $offices = SpiritualOffice::query()->where('world_id', $world->id)->pluck('rank')->all();
        $this->assertContains(SpiritualOfficeRank::BISHOP, $offices);
        $this->assertContains(SpiritualOfficeRank::ABBOT, $offices);
        $this->assertContains(SpiritualOfficeRank::PRIEST, $offices);
        $this->assertContains(SpiritualOfficeRank::POPE, $offices);
        $this->assertSame(0, Title::query()->where('world_id', $world->id)->whereIn('rank', $offices)->count());

        $report = (new WorldIntegrityValidator())->validate($world);
        $this->assertTrue($report->passed(), json_encode($report->toArray()));

        $levy = app(RaiseArmy::class)->execute($slice->ruler, $slice->territory('salon'), 120, 'Levy of Salon');
        $this->assertTrue($levy->is_active);
        $this->assertSame(120, (int) $levy->strength);

        $aix = $slice->territory('aix');
        $miramas = $slice->territory('miramas');
        app(MoveArmy::class)->execute($levy, $aix);
        app(MoveArmy::class)->execute($levy->fresh(), $miramas);

        $enemy = $slice->enemyArmy();
        $this->assertNotNull($enemy);
        $humanBattle = app(ResolveBattle::class)->execute($levy->fresh(), $enemy->fresh(), 'human');
        $this->assertSame('human', $humanBattle->kind);
        $this->assertContains($humanBattle->winner, ['attacker', 'defender']);
        $this->assertTrue($levy->fresh()->is_active, 'Player host should survive the Miramas fight.');

        $choices = [
            BeatKey::PLAGUE_APPEARS => 'send_physicians',
            BeatKey::REFUGEES_ARRIVE => 'admit',
            BeatKey::NOBLE_REFUSES_AID => 'demand_compliance',
            BeatKey::MONASTERY_REQUESTS_RESOURCES => 'grant',
            BeatKey::RUMORS_OF_HERESY => 'ask_bishop',
            BeatKey::CULT_DISCOVERY => 'arrest',
            BeatKey::DEMONIC_MANIFESTATION => 'call_clergy',
            BeatKey::CLERGY_RESPONSE => 'exorcism',
            BeatKey::MILITARY_RESPONSE => 'attack_manifestation',
            BeatKey::AFTERMATH => 'record_chronicle',
        ];

        foreach ($choices as $key => $option) {
            $this->resolveWhenDue($world->id, $key, $option);
        }

        $this->assertSame(1, PlagueWave::query()->where('world_id', $world->id)->count());
        $this->assertTrue(Cult::query()->where('world_id', $world->id)->where('revealed', true)->exists());
        $this->assertTrue(HeresyPresence::query()->where('world_id', $world->id)->where('is_current', true)->exists());
        $this->assertTrue(
            SupernaturalOverlay::query()
                ->where('territory_id', $aix->id)
                ->where('is_current', true)
                ->where('kind', 'haunted')
                ->exists()
        );

        $this->assertTrue(
            Battle::query()->where('world_id', $world->id)->where('kind', 'supernatural')->exists()
        );
        $this->assertTrue(
            Army::query()->where('world_id', $world->id)->where('kind', ArmyKind::DEMONIC)->exists()
        );

        $standing = (int) ChurchRelation::query()->where('character_id', $slice->ruler->id)->value('standing');
        $this->assertGreaterThan($standingStart, $standing);

        $aixNow = (int) Territory::query()->whereKey($aix->id)->value('population');
        $salonNow = (int) Territory::query()->where('world_id', $world->id)->where('key', 'salon')->value('population');
        $this->assertLessThan($aixStart, $aixNow);
        $this->assertNotSame($salonStart, $salonNow);
        $this->assertTrue(
            CorruptionState::query()->where('world_id', $world->id)->where('intensity', '>', 0)->exists()
        );

        $world->refresh();
        $stage = $world->apocalypseState->stage ?: $world->apocalypseState->phase_key;
        $this->assertTrue(
            ApocalypseStage::atLeast((string) $stage, ApocalypseStage::THINNING_VEIL),
            'Apocalypse should have left ordinary order. Stage: '.$stage
        );

        foreach (BeatKey::sequence() as $key) {
            $this->assertSame(
                GameEventStatus::RESOLVED,
                GameEvent::query()->where('world_id', $world->id)->where('event_key', $key)->value('status')
            );
        }
    }

    private function resolveWhenDue(int $worldId, string $key, string $option): void
    {
        $advance = app(AdvanceWorldCalendar::class);
        $resolver = app(ResolveGameEvent::class);
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $event = GameEvent::query()->where('world_id', $worldId)->where('event_key', $key)->firstOrFail();
            if ($event->status === GameEventStatus::AWAITING_DECISION) {
                $resolver->execute($event, $option);

                return;
            }
            $this->assertSame(GameEventStatus::SCHEDULED, $event->status);
            $advance->execute($event->world, 1);
        }

        $this->fail('Event '.$key.' never opened for a decision.');
    }
}
