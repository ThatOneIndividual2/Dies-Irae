<?php

namespace App\Domain\Warfare\Ports;

interface PopulationPort
{
    public function population(int $worldId, int $territoryId): int;

    public function baselinePopulation(int $worldId, int $territoryId): int;

    public function levyEligibleFraction(int $worldId, int $territoryId): float;
}
