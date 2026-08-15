<?php

namespace App\Domain\Warfare\Ports;

/**
 * Optional Hell port. Human vs human warfare must not call mutating methods.
 */
interface HellPort
{
    public function corruption(int $worldId, int $territoryId): int;

    public function nearestPortal(int $worldId, int $territoryId, int $factionId): ?PortalSnapshot;

    public function manifestationCap(int $worldId, int $factionId): int;
}
