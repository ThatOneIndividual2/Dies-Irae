<?php

namespace App\Domain\Warfare\State;

use App\Domain\Warfare\Enums\SanctityState;

final class AftermathReport
{
    public function __construct(
        public int $worldId,
        public int $warId,
        public int $territoryId,
        public string $warKind,
        public int $corpses,
        public string $corpseHandling,
        public int $mundaneDiseaseRisk,
        public int $plagueExposure,
        public int $settlementMoraleDelta,
        public int $corruptionDelta,
        public int $localFaithDelta,
        public int $refugees,
        public string $refugeeCause,
        public string $sanctityAfter,
        public bool $possessionEvent,
        public bool $desecration,
        public bool $hellOverlayApplied,
        public bool $infiltrationApplied,
        public array $notes = [],
    ) {
    }

    public function isMundaneHumanAftermath(): bool
    {
        return $this->corruptionDelta === 0
            && $this->plagueExposure === 0
            && $this->possessionEvent === false
            && $this->desecration === false
            && $this->hellOverlayApplied === false
            && $this->infiltrationApplied === false
            && $this->sanctityAfter === SanctityState::ORDINARY;
    }
}
