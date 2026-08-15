<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\ConclaveSession;
use App\Domain\Papacy\PapacyEngine;

final class OpenPapalVacancy
{
    public function execute(PapacyEngine $engine, string $date, string $cause = 'death'): ConclaveSession
    {
        return $engine->openVacancy($date, $cause);
    }
}
