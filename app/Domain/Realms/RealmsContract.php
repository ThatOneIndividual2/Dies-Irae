<?php

namespace App\Domain\Realms;

/**
 * Domain contract for Realms. Bound by RealmsServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface RealmsContract
{
    public function domainKey(): string;
}
