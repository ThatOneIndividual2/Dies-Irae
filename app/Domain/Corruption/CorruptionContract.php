<?php

namespace App\Domain\Corruption;

/**
 * Domain contract for Corruption. Bound by CorruptionServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface CorruptionContract
{
    public function domainKey(): string;
}
