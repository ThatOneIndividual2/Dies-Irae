<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;

final class MoveArmyHost
{
    public function execute(CatastropheEngine $engine, string $armyId, string $toSettlementId): void
    {
        $engine->moveArmy($armyId, $toSettlementId);
    }
}
