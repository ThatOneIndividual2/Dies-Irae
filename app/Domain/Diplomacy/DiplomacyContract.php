<?php

namespace App\Domain\Diplomacy;

/**
 * Domain contract for Diplomacy. Bound by DiplomacyServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface DiplomacyContract
{
    public function domainKey(): string;
}
