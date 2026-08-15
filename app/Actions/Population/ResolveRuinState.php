<?php

namespace App\Actions\Population;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Population\RuinStateMachine;

final class ResolveRuinState
{
    public function execute(CatastropheEngine $engine, string $settlementId): string
    {
        $settlement = $engine->settlement($settlementId);
        $machine = new RuinStateMachine();
        $before = $settlement->ruinState;
        $machine->apply($settlement, $settlement->hellOccupation);

        return $before;
    }
}
