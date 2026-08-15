<?php

namespace App\Domain\Geography;

/**
 * Domain contract for Geography. Bound by GeographyServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface GeographyContract
{
    public function domainKey(): string;
}
