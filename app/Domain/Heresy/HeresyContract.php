<?php

namespace App\Domain\Heresy;

/**
 * Domain contract for Heresy. Bound by HeresyServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface HeresyContract
{
    public function domainKey(): string;
}
