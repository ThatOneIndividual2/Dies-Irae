<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\AntipopeSchism;
use App\Domain\Papacy\PapacyEngine;

final class RaiseAntipope
{
    /**
     * @param list<string> $electorIds
     * @param list<string> $seeIds
     * @param list<string> $realmIds
     */
    public function execute(
        PapacyEngine $engine,
        string $claimantId,
        array $electorIds,
        array $seeIds = [],
        array $realmIds = [],
        ?string $recognizedId = null
    ): AntipopeSchism {
        return $engine->raiseAntipope($claimantId, $electorIds, $seeIds, $realmIds, $recognizedId);
    }
}
