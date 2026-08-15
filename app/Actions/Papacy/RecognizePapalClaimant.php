<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\PapacyEngine;
use App\Domain\Papacy\PapalObedience;

final class RecognizePapalClaimant
{
    public function execute(
        PapacyEngine $engine,
        string $subjectKind,
        string $subjectId,
        string $claimantId
    ): PapalObedience {
        return $engine->recognize($subjectKind, $subjectId, $claimantId);
    }
}
