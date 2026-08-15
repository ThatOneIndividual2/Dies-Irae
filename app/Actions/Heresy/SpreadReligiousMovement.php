<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\MovementPresence;
use App\Models\ReligiousMovement;
use App\Models\Territory;
use Carbon\CarbonInterface;

final class SpreadReligiousMovement
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        ReligiousMovement $movement,
        Territory $from,
        Territory $to,
        string $vector,
        Character $actor,
        CarbonInterface $date
    ): MovementPresence {
        return $this->engine->spread($movement, $from, $to, $vector, $actor, $date);
    }
}
