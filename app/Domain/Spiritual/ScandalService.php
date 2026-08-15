<?php

namespace App\Domain\Spiritual;

use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\Scandal;
use App\Models\World;
use Carbon\CarbonInterface;

final class ScandalService
{
    public function record(
        World $world,
        Character $character,
        string $facet,
        int $severity,
        CarbonInterface $date,
        string $publicity = 'settlement',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): Scandal {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'scandal character');

        return Scandal::query()->create([
            'world_id' => $world->id,
            'character_id' => $character->id,
            'facet' => $facet,
            'severity' => max(0, min(100, $severity)),
            'publicity' => $publicity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'broke_date' => $date->toDateString(),
            'is_current' => true,
            'metadata' => $metadata,
        ]);
    }

    public function currentFor(Character $character)
    {
        return Scandal::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->orderByDesc('severity')
            ->get();
    }
}
