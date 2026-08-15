<?php

namespace App\Domain\Titles;

/**
 * Domain contract for Titles. Bound by TitlesServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface TitlesContract
{
    public function domainKey(): string;
}
