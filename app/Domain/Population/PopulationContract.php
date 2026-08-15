<?php

namespace App\Domain\Population;

/**
 * Domain contract for Population. Bound by PopulationServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface PopulationContract
{
    public function domainKey(): string;
}
