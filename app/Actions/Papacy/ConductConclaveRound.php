<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\BallotRound;
use App\Domain\Papacy\PapacyEngine;

final class ConductConclaveRound
{
    public function execute(PapacyEngine $engine): BallotRound
    {
        return $engine->voteRound();
    }
}
