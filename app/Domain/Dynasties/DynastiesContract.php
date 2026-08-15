<?php

namespace App\Domain\Dynasties;

/**
 * Domain contract for Dynasties. Bound by DynastiesServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface DynastiesContract
{
    public function domainKey(): string;
}
