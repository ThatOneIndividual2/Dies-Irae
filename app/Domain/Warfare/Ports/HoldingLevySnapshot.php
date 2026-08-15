<?php

namespace App\Domain\Warfare\Ports;

final class HoldingLevySnapshot
{
    public function __construct(
        public int $worldId,
        public int $holdingId,
        public int $territoryId,
        public int $ownerCharacterId,
        public int $baseLevy,
        public int $contractLevyRate,
        public int $currentPopulation,
        public int $baselinePopulation,
        public string $ruinState,
        public int $fortification,
        public bool $isHolySite,
    ) {
    }
}
