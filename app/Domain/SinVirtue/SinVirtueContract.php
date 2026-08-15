<?php

namespace App\Domain\SinVirtue;

/**
 * Domain contract for SinVirtue. Bound by SinVirtueServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface SinVirtueContract
{
    public function domainKey(): string;
}
