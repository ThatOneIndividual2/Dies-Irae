<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\AntipopeSchism;
use App\Domain\Papacy\PapacyEngine;

final class ReconcilePapalSchism
{
    public function execute(PapacyEngine $engine, string $survivingClaimantId): AntipopeSchism
    {
        return $engine->reconcile($survivingClaimantId);
    }
}
