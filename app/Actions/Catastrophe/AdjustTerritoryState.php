<?php

namespace App\Actions\Catastrophe;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\CorruptionState;
use App\Models\DespairState;
use App\Models\Territory;
use App\Models\World;

final class AdjustTerritoryState
{
    public function addPopulation(Territory $territory, int $delta): Territory
    {
        return Transactional::run(function () use ($territory, $delta) {
            $locked = Territory::query()->whereKey($territory->id)->lockForUpdate()->firstOrFail();
            $next = (int) $locked->population + $delta;
            if ($next < 0) {
                $next = 0;
            }
            $locked->population = $next;
            $locked->levy_available = (int) floor($next * (float) config('game.levy_ratio'));
            if ($next < (int) config('game.ruin_population_threshold')) {
                $locked->ruin_state = 'depopulated';
            } elseif (($locked->ruin_state ?? 'intact') === 'depopulated') {
                $locked->ruin_state = 'intact';
            }
            $locked->save();

            return $locked->fresh();
        });
    }

    public function addCorruption(string $subjectType, int $subjectId, int $worldId, int $delta, string $source): CorruptionState
    {
        return Transactional::run(function () use ($subjectType, $subjectId, $worldId, $delta, $source) {
            $row = CorruptionState::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->lockForUpdate()
                ->first();

            $world = World::query()->findOrFail($worldId);
            if (!$row) {
                return CorruptionState::query()->create([
                    'world_id' => $worldId,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'intensity' => max(0, min(100, $delta)),
                    'source' => $source,
                    'started_date' => $world->current_date->toDateString(),
                    'recorded_on' => $world->current_date->toDateString(),
                ]);
            }

            $row->intensity = max(0, min(100, (int) $row->intensity + $delta));
            $row->source = $source;
            $row->started_date = $world->current_date->toDateString();
            $row->recorded_on = $world->current_date->toDateString();
            $row->save();

            return $row->fresh();
        });
    }

    public function addDespair(Territory $territory, int $delta): DespairState
    {
        return Transactional::run(function () use ($territory, $delta) {
            $world = World::query()->findOrFail($territory->world_id);
            $row = DespairState::query()
                ->where('territory_id', $territory->id)
                ->lockForUpdate()
                ->first();
            if (!$row) {
                return DespairState::query()->create([
                    'world_id' => $territory->world_id,
                    'territory_id' => $territory->id,
                    'intensity' => max(0, min(100, $delta)),
                    'recorded_on' => $world->current_date->toDateString(),
                ]);
            }
            $row->intensity = max(0, min(100, (int) $row->intensity + $delta));
            $row->recorded_on = $world->current_date->toDateString();
            $row->save();

            return $row->fresh();
        });
    }

    public function assertWorld(Territory $territory, int $worldId): void
    {
        WorldBoundary::assertSameWorld($worldId, (int) $territory->world_id, 'territory state');
    }
}
