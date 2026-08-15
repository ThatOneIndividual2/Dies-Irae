<?php

namespace App\Domain\Warfare\Ports;

final class NullHellPort implements HellPort
{
    public function corruption(int $worldId, int $territoryId): int
    {
        return 0;
    }

    public function nearestPortal(int $worldId, int $territoryId, int $factionId): ?PortalSnapshot
    {
        return null;
    }

    public function manifestationCap(int $worldId, int $factionId): int
    {
        return 0;
    }
}
