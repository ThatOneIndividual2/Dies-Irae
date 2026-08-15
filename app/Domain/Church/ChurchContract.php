<?php

namespace App\Domain\Church;

/**
 * Domain contract for Church. Bound by ChurchServiceProvider.
 * The Church is a peer authority to secular titles, not a cosmetic faction.
 */
interface ChurchContract
{
    public function domainKey(): string;

    public function authority(): ChurchAuthority;
}
