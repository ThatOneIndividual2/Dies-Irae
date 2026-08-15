<?php

namespace App\Domain\Warfare\State;

final class OccupationResult
{
    public function __construct(
        public int $territoryId,
        public string $mode,
        public bool $militaryControlTransferred,
        public bool $legalOwnershipTransferred,
        public bool $hellOverlayApplied,
        public bool $infiltrationApplied,
        public bool $corruptionApplied,
        public int $controllerBelligerentId,
        public int $ownerBelligerentId,
    ) {
    }
}
