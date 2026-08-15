<?php

namespace App\Domain\Succession;

/**
 * Domain contract for Succession. Bound by SuccessionServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface SuccessionContract
{
    public function domainKey(): string;
}
