<?php

namespace App\Actions\Army;

use App\Domain\Enums\ArmyKind;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Army;
use App\Models\Character;
use App\Models\Territory;
use RuntimeException;

final class RaiseArmy
{
    public function execute(Character $commander, Territory $territory, int $strength, string $name = 'Levy host'): Army
    {
        WorldBoundary::assertSameWorldEntities('raise army', $commander, $territory);
        if ($strength < 1) {
            throw new RuntimeException('Cannot raise an empty army.');
        }
        if ((int) $territory->levy_available < $strength) {
            throw new RuntimeException('Not enough levy available in this settlement.');
        }

        return Transactional::run(function () use ($commander, $territory, $strength, $name) {
            $locked = Territory::query()->whereKey($territory->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->levy_available < $strength) {
                throw new RuntimeException('Not enough levy available in this settlement.');
            }

            $locked->levy_available = (int) $locked->levy_available - $strength;
            $locked->save();

            return Army::query()->create([
                'world_id' => $commander->world_id,
                'name' => $name,
                'kind' => ArmyKind::LEVY,
                'commander_character_id' => $commander->id,
                'owner_character_id' => $commander->id,
                'territory_id' => $locked->id,
                'strength' => $strength,
                'status' => 'idle',
                'is_active' => true,
            ]);
        });
    }
}
