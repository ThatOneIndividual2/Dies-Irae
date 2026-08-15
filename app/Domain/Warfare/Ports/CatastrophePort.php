<?php

namespace App\Domain\Warfare\Ports;

/**
 * Optional catastrophe port. Ordinary feudal war may still emit mundane
 * camp-fever from corpses; apocalyptic plague is gated by war profile.
 */
interface CatastrophePort
{
    public function plagueIntensity(int $worldId, int $territoryId): int;

    public function settlementMorale(int $worldId, int $territoryId): int;

    public function campFeverRiskFromCorpses(int $corpses): int;
}
