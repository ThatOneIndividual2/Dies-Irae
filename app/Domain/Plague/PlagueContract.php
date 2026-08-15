<?php

namespace App\Domain\Plague;

/**
 * Domain contract for Plague. Bound by PlagueServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface PlagueContract
{
    public function domainKey(): string;
}
