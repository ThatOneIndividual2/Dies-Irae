<?php

namespace App\Actions\Church;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\ClergyStatus;
use Carbon\CarbonInterface;

final class SetClergyStatus
{
    public function execute(
        Character $character,
        string $status,
        CarbonInterface $date,
        array $attributes = []
    ): ClergyStatus {
        WorldBoundary::assertSameWorldEntities('set clergy status', $character);

        return Transactional::run(function () use ($character, $status, $date, $attributes) {
            $current = ClergyStatus::query()
                ->where('character_id', $character->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current) {
                $current->ended_date = $date->toDateString();
                $current->is_current = null;
                $current->save();
            }

            return ClergyStatus::query()->create([
                'world_id' => $character->world_id,
                'character_id' => $character->id,
                'status' => $status,
                'orders_grade' => $attributes['orders_grade'] ?? $status,
                'religious_state' => $attributes['religious_state'] ?? 'secular_cleric',
                'regularity' => $attributes['regularity'] ?? 'regular',
                'religious_order_id' => $attributes['religious_order_id'] ?? null,
                'monastery_id' => $attributes['monastery_id'] ?? null,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        });
    }
}
