<?php

namespace App\Domain\World;

/**
 * Domain contract for World. Bound by WorldServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface WorldContract
{
    public function domainKey(): string;
}
