<?php

namespace App\Domain\Characters;

/**
 * Domain contract for Characters. Bound by CharactersServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface CharactersContract
{
    public function domainKey(): string;
}
