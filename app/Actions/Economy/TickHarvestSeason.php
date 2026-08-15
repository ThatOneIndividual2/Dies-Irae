<?php

namespace App\Actions\Economy;

use App\Domain\Economy\EconomyEngine;

final class TickHarvestSeason
{
    public function execute(EconomyEngine $engine, ?string $phase = null): array
    {
        return $engine->runPhase($phase);
    }
}
