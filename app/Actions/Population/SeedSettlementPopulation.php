<?php

namespace App\Actions\Population;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\SettlementKind;
use App\Domain\Population\PopulationCohorts;
use App\Domain\Population\Settlement;

final class SeedSettlementPopulation
{
    public function execute(
        CatastropheEngine $engine,
        string $id,
        string $name,
        PopulationCohorts $cohorts,
        string $kind = SettlementKind::TOWN
    ): Settlement {
        return $engine->addSettlement(Settlement::found($engine->worldId, $id, $name, $kind, $cohorts));
    }
}
