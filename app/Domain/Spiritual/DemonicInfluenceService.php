<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\DemonicInfluenceStage;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\DemonicInfluence;
use App\Models\World;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class DemonicInfluenceService
{
    public function current(Character $character): ?DemonicInfluence
    {
        return DemonicInfluence::query()
            ->where('character_id', $character->id)
            ->where('is_active', true)
            ->first();
    }

    public function set(
        World $world,
        Character $character,
        string $stage,
        int $intensity,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): DemonicInfluence {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'demonic influence');
        if (!in_array($stage, DemonicInfluenceStage::all(), true)) {
            throw new InvalidArgumentException("Unknown demonic stage: {$stage}");
        }

        $existing = $this->current($character);
        if ($existing) {
            $existing->is_active = false;
            $existing->ended_on = $date->toDateString();
            $existing->save();
        }

        return DemonicInfluence::query()->create([
            'world_id' => $world->id,
            'character_id' => $character->id,
            'stage' => $stage,
            'intensity' => max(0, min(100, $intensity)),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'started_on' => $date->toDateString(),
            'is_active' => true,
            'metadata' => $metadata,
        ]);
    }

    public function clear(Character $character, CarbonInterface $date): ?DemonicInfluence
    {
        $existing = $this->current($character);
        if ($existing === null) {
            return null;
        }
        $existing->is_active = false;
        $existing->ended_on = $date->toDateString();
        $existing->save();

        return $existing;
    }
}
