<?php

namespace App\Actions\Economy;

use App\Domain\Economy\EconomyEngine;
use App\Domain\Economy\GrainShipment;

final class MoveGrain
{
    public function execute(
        EconomyEngine $engine,
        string $fromId,
        string $toId,
        int $amount,
        string $mode,
        string $actor = 'ruler'
    ): GrainShipment {
        return $engine->moveGrain($fromId, $toId, $amount, $mode, $actor);
    }
}
