<?php

namespace App\Actions\Papacy;

use App\Domain\Papacy\PapacyEngine;
use App\Domain\Papacy\SecularPressure;

final class ApplySecularPapalPressure
{
    /**
     * @param list<string>|null $targetElectorIds
     */
    public function execute(
        PapacyEngine $engine,
        string $rulerId,
        string $kind,
        int $magnitude,
        ?string $candidateId = null,
        ?array $targetElectorIds = null,
        ?string $rulerRealmId = null
    ): SecularPressure {
        return $engine->pressure($rulerId, $kind, $magnitude, $candidateId, $targetElectorIds, $rulerRealmId);
    }
}
