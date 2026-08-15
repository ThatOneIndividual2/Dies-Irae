<?php

namespace App\Domain\Warfare\State;

final class MovementResult
{
    public function __construct(
        public int $armyId,
        public int $fromTerritoryId,
        public int $toTerritoryId,
        public int $days,
        public int $supplyAfter,
        public bool $starving,
        public bool $blockedByPortal,
        public bool $manifestationCollapsed,
        public int $terrainCorruptionDelta,
    ) {
    }
}
