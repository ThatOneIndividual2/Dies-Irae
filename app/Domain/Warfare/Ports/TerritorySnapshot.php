<?php

namespace App\Domain\Warfare\Ports;

final class TerritorySnapshot
{
    public function __construct(
        public int $worldId,
        public int $territoryId,
        public string $name,
        public string $terrain,
        public int $controllerBelligerentId,
        public int $ownerBelligerentId,
        public int $fortification,
        public array $neighborIds,
        public int $movementDays,
    ) {
    }
}
