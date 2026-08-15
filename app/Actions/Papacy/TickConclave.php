<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\PapacyEngine;

final class TickConclave
{
    public function execute(PapacyEngine $engine): void
    {
        $engine->tick();
    }
}
