<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\ConclaveSession;
use App\Domain\Papacy\PapacyEngine;

final class AssembleConclave
{
    public function execute(PapacyEngine $engine): ConclaveSession
    {
        return $engine->assemble();
    }
}
