<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\CardinalElector;
use App\Domain\Papacy\PapacyEngine;

final class EnthronePope
{
    public function execute(PapacyEngine $engine): CardinalElector
    {
        return $engine->enthrone();
    }
}
