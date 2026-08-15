<?php

namespace App\Domain\Economy;

/**
 * Domain contract for Economy. Bound by EconomyServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface EconomyContract
{
    public function domainKey(): string;
}
