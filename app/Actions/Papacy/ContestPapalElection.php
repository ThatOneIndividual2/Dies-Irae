<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\PapacyEngine;

final class ContestPapalElection
{
    public function execute(PapacyEngine $engine, string $byElectorId): void
    {
        $engine->contestElection($byElectorId);
    }
}
