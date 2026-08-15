<?php

namespace App\Domain\Warfare;

/**
 * Domain contract for Warfare. Bound by WarfareServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface WarfareContract
{
    public function domainKey(): string;
}
