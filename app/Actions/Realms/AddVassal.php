<?php

namespace App\Actions\Realms;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\FeudalContract;
use App\Models\VassalRelationship;
use Carbon\CarbonInterface;
use RuntimeException;

final class AddVassal
{
    public function execute(Character $liege, Character $vassal, CarbonInterface $date, int $taxRate = 10, int $levyRate = 20): VassalRelationship
    {
        WorldBoundary::assertSameWorldEntities('vassalage', $liege, $vassal);
        if ((int) $liege->id === (int) $vassal->id) {
            throw new RuntimeException('A character cannot be their own vassal.');
        }

        return Transactional::run(function () use ($liege, $vassal, $date, $taxRate, $levyRate) {
            $existing = VassalRelationship::query()
                ->where('vassal_character_id', $vassal->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new RuntimeException('Vassal already has a current liege.');
            }

            $link = VassalRelationship::query()->create([
                'world_id' => $liege->world_id,
                'vassal_character_id' => $vassal->id,
                'liege_character_id' => $liege->id,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $contract = FeudalContract::query()->create([
                'world_id' => $liege->world_id,
                'liege_character_id' => $liege->id,
                'vassal_character_id' => $vassal->id,
                'tax_rate' => $taxRate,
                'levy_rate' => $levyRate,
                'effective_date' => $date->toDateString(),
            ]);
            $link->contract_id = $contract->id;
            $link->save();

            return $link;
        });
    }
}
