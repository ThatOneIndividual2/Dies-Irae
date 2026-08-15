<?php

namespace App\Domain\Time;

/**
 * Domain contract for Time. Bound by TimeServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface TimeContract
{
    public function domainKey(): string;
}
