<?php

namespace App\Domain\Warfare\State;

use App\Domain\Warfare\Enums\WarGoalType;

final class WarGoal
{
    public function __construct(
        public string $type,
        public ?int $targetTerritoryId,
        public int $scoreToWin = 100,
    ) {
        if (!in_array($type, WarGoalType::all(), true)) {
            throw new \InvalidArgumentException("Unknown war goal: {$type}");
        }
    }
}
