<?php

namespace App\Domain\Hell\Ports;

/**
 * Population/plague domain owns souls. Hell reports intended deltas; it does not census.
 */
interface PopulationOverlay
{
    public function applyHellMortality(string $territoryId, int $soulsDelta, string $cause): void;

    public function plagueBurden(string $territoryId): int;
}
