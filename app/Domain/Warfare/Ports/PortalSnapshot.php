<?php

namespace App\Domain\Warfare\Ports;

final class PortalSnapshot
{
    public function __construct(
        public int $worldId,
        public int $territoryId,
        public int $factionId,
        public int $strength,
        public bool $open,
    ) {
    }
}
