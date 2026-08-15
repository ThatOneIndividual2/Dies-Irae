<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\TemptationResolution;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\Temptation;
use App\Models\World;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class TemptationService
{
    public function open(
        World $world,
        Character $character,
        string $vice,
        int $intensity,
        CarbonInterface $date,
        string $stage = 'pressing',
        ?string $sourceType = null,
        ?int $sourceId = null
    ): Temptation {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'temptation character');
        if (!in_array($vice, CapitalVice::all(), true)) {
            throw new InvalidArgumentException("Temptation must target a capital vice, not {$vice}");
        }

        return Temptation::query()->create([
            'world_id' => $world->id,
            'character_id' => $character->id,
            'vice' => $vice,
            'intensity' => max(0, min(100, $intensity)),
            'stage' => $stage,
            'resolution' => TemptationResolution::OPEN,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'opened_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function resolve(
        Temptation $temptation,
        string $resolution,
        CarbonInterface $date,
        SpiritualStateService $states
    ): Temptation {
        if (!in_array($resolution, TemptationResolution::all(), true) || $resolution === TemptationResolution::OPEN) {
            throw new InvalidArgumentException('Invalid temptation resolution');
        }

        $temptation->resolution = $resolution;
        $temptation->resolved_date = $date->toDateString();
        $temptation->is_current = null;
        $temptation->save();

        $character = $temptation->character;
        $world = $temptation->world;
        if ($resolution === TemptationResolution::YIELDED) {
            $states->applyDeltas($world, $character, [$temptation->vice => intdiv((int) $temptation->intensity, 4)], 'temptation_yielded', $date, Temptation::class, $temptation->id);
        }
        if ($resolution === TemptationResolution::RESISTED) {
            $states->applyDeltas($world, $character, [$temptation->vice => -intdiv((int) $temptation->intensity, 6), 'faith' => 2], 'temptation_resisted', $date, Temptation::class, $temptation->id);
        }

        return $temptation->fresh();
    }

    public function currentFor(Character $character)
    {
        return Temptation::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->get();
    }
}
