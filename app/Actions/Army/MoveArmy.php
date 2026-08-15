<?php

namespace App\Actions\Army;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Army;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use RuntimeException;

final class MoveArmy
{
    public function execute(Army $army, Territory $destination): Army
    {
        WorldBoundary::assertSameWorldEntities('move army', $army, $destination);
        if (!$army->is_active) {
            throw new RuntimeException('Army is no longer in the field.');
        }
        if ((int) $army->territory_id === (int) $destination->id) {
            throw new RuntimeException('Army is already in that settlement.');
        }

        $adjacent = TerritoryAdjacency::query()
            ->where('from_territory_id', $army->territory_id)
            ->where('to_territory_id', $destination->id)
            ->exists();

        if (!$adjacent) {
            throw new RuntimeException('Armies can only march into adjacent settlements.');
        }

        return Transactional::run(function () use ($army, $destination) {
            $locked = Army::query()->whereKey($army->id)->lockForUpdate()->firstOrFail();
            $locked->territory_id = $destination->id;
            $locked->status = 'marching';
            $locked->save();

            return $locked->fresh();
        });
    }
}
