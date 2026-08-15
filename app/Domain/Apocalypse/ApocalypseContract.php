<?php

namespace App\Domain\Apocalypse;

/**
 * Domain contract for Apocalypse. Bound by ApocalypseServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface ApocalypseContract
{
    public function domainKey(): string;
}
