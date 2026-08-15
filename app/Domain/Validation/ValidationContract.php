<?php

namespace App\Domain\Validation;

/**
 * Domain contract for Validation. Bound by ValidationServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface ValidationContract
{
    public function domainKey(): string;
}
