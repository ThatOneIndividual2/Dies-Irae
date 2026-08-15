<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Enums\UnitCategory;

/**
 * Numeric donor reuse from Feudalism config/feudalism.php, plus Dies Irae extensions.
 * Keep numbers here so doctrines stay flag-based.
 */
final class WarfareBalance
{
    public int $pressureToOccupy = 100;

    /** @var array<string, float> */
    public array $unitPower = [
        'infantry' => 1.0,
        'archers' => 1.2,
        'cavalry' => 2.0,
        'siege' => 3.0,
    ];

    public float $tickCasualtyRate = 0.08;

    public float $moraleFloor = 0.4;

    public float $moraleCeil = 1.2;

    public int $starveSupplyThreshold = 15;

    public int $dailyFoodDrain = 8;

    public int $dailyTitheDrain = 5;

    public int $dailyPlunderGain = 4;

    public int $dailyDevourDrain = 6;

    public int $portalRangeDays = 3;

    public function categoryWeight(string $category): float
    {
        return match ($category) {
            UnitCategory::LEVY => $this->unitPower['infantry'],
            UnitCategory::MEN_AT_ARMS => $this->unitPower['cavalry'],
            UnitCategory::CONSECRATED => 1.8,
            UnitCategory::CULTIST => 0.9,
            UnitCategory::DEMONIC => 2.2,
            UnitCategory::CORRUPTED => 1.4,
            UnitCategory::SIEGE => $this->unitPower['siege'],
            default => 1.0,
        };
    }

    /**
     * Donor levy math: floor(base * rate / 100).
     */
    public function contractLevyDue(int $baseLevy, int $levyRate): int
    {
        $rate = max(0, min(100, $levyRate));

        return (int) floor($baseLevy * ($rate / 100));
    }
}
