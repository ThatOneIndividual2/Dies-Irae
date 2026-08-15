<?php

namespace App\Domain\Warfare\Ports;

interface GeographyPort
{
    public function territory(int $worldId, int $territoryId): TerritorySnapshot;

    /**
     * @return int[]
     */
    public function neighbors(int $worldId, int $territoryId): array;

    public function movementDays(int $worldId, int $fromTerritoryId, int $toTerritoryId): int;

    public function areNeighbors(int $worldId, int $a, int $b): bool;
}
