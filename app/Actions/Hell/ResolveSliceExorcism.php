<?php

namespace App\Actions\Hell;

use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Domain\Enums\ArmyKind;
use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\Enums\CountermeasureKind;
use App\Domain\Hell\Enums\CountermeasureResult;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\State\TerritoryThreat;
use App\Domain\Hell\State\ThreatWorld;
use App\Models\Army;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\CorruptionState;
use App\Models\CountermeasureAttemptRecord;
use App\Models\DemonicInfluence;
use App\Models\DespairState;
use App\Models\Territory;
use App\Models\World;

/**
 * Parish exorcism goes through the hell countermeasure engine.
 * The playable host is weakened from the result, not from a fixed corruption cut.
 */
final class ResolveSliceExorcism
{
    public function __construct(private AdjustTerritoryState $state)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world, Territory $territory, Character $minister): array
    {
        $corruption = (int) (CorruptionState::query()
            ->where('subject_type', 'territory')
            ->where('subject_id', $territory->id)
            ->value('intensity') ?? 24);
        $despair = (int) (DespairState::query()->where('territory_id', $territory->id)->value('intensity') ?? 18);
        $standing = (int) (ChurchRelation::query()->where('character_id', $minister->id)->value('standing') ?? 20);

        $threat = new ThreatWorld();
        $threat->worldId = (int) $world->id;
        $threat->seed = (string) ($world->simulation_seed ?: $world->id);
        $threat->date = $world->current_date->toDateString();
        $threat->apocalypseIntensity = 28;

        $place = new TerritoryThreat((string) $territory->key);
        $place->legalTitleId = 'law:'.$territory->key;
        $place->incursionState = IncursionState::MANIFESTED;
        $place->corruption = $corruption;
        $place->despair = $despair;
        $place->localManifestation = 36;
        $place->population = (int) $territory->population;
        $place->clergyPresence = 50;
        $threat->addTerritory($place);

        $attempt = new CountermeasureAttempt(CountermeasureKind::EXORCISM, (string) $territory->key);
        $attempt->actorCharacterId = (string) $minister->id;
        $attempt->factors = [
            'clergy_present' => true,
            'clergy_rank_weight' => 80,
            'clergy_legitimacy' => 75,
            'relic_authenticity' => 0,
            'relic_power' => 0,
            'holy_order_support' => 0,
            'accumulated_prayer' => 20,
            'devotional_standing' => max(0, min(100, $standing + 30)),
        ];

        $engine = DemonicThreatEngine::fromDataDirectory(database_path('data/hell'));
        $outcome = $engine->attemptCountermeasure($threat, $attempt);

        CountermeasureAttemptRecord::query()->create([
            'world_id' => $world->id,
            'territory_id' => $territory->id,
            'kind' => $outcome->kind,
            'result' => $outcome->result,
            'score' => $outcome->score,
            'actor_character_id' => $minister->id,
            'character_died' => $outcome->characterDied,
            'breach_closed' => $outcome->breachClosed,
            'factors' => $attempt->factors,
            'attempted_on' => $world->current_date->toDateString(),
        ]);

        [$corruptionDelta, $despairDelta, $hostDelta, $influenceDelta] = match ($outcome->result) {
            CountermeasureResult::SUCCESS => [-8, -6, -15, -2],
            CountermeasureResult::PARTIAL => [-4, -3, -8, -1],
            CountermeasureResult::BACKLASH => [8, 5, 5, 2],
            default => [0, 0, 0, 0],
        };

        if ($corruptionDelta !== 0) {
            $this->state->addCorruption('territory', (int) $territory->id, (int) $world->id, $corruptionDelta, 'exorcism');
        }
        if ($despairDelta !== 0) {
            $this->state->addDespair($territory, $despairDelta);
        }

        $host = Army::query()
            ->where('world_id', $world->id)
            ->where('kind', ArmyKind::DEMONIC)
            ->where('is_active', true)
            ->first();
        if ($host && $hostDelta !== 0) {
            $next = (int) $host->strength + $hostDelta;
            $host->strength = $hostDelta < 0 ? max(10, $next) : min(200, $next);
            $host->save();
        }

        $influence = DemonicInfluence::query()
            ->where('world_id', $world->id)
            ->where('territory_id', $territory->id)
            ->where('is_active', true)
            ->first();
        if ($influence && $influenceDelta !== 0) {
            $influence->intensity = max(0, (int) $influence->intensity + $influenceDelta);
            $influence->save();
        }

        return [
            'result' => $outcome->result,
            'score' => $outcome->score,
            'from' => $outcome->fromState,
            'to' => $outcome->toState,
        ];
    }
}
