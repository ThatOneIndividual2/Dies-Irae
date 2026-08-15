<?php

namespace App\Domain\Sacred\Policies;

use App\Domain\Enums\MiracleCategory;
use App\Models\Miracle;
use Carbon\CarbonInterface;
use DomainException;

final class MiracleRarityPolicy
{
    public function assertClaimAllowed(
        int $worldId,
        ?string $placeType,
        ?int $placeId,
        string $category,
        string $sourceType,
        CarbonInterface $date
    ): void {
        if (!in_array($category, MiracleCategory::all(), true)) {
            throw new DomainException("Unknown miracle category: {$category}");
        }
        if (config('sacred.miracle.required_source') && $sourceType === '') {
            throw new DomainException('A miracle claim needs a precipitating event. It is not a spell.');
        }

        if ($placeType === null || $placeId === null) {
            return;
        }

        $year = $date->year;
        $count = Miracle::query()
            ->where('world_id', $worldId)
            ->where('place_type', $placeType)
            ->where('place_id', $placeId)
            ->whereYear('occurred_date', $year)
            ->count();

        $max = (int) config('sacred.miracle.max_claims_per_place_per_year', 3);
        if ($count >= $max) {
            throw new DomainException('Miracle claims at this place are already at this year\'s rarity limit.');
        }
    }
}
