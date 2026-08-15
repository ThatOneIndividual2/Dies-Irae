<?php

namespace App\Domain\Papacy;

/**
 * Domain contract for Papacy succession. Bound by PapacyServiceProvider.
 * The papacy is an office with institutional succession, not a realm rank.
 */
interface PapacyContract
{
    public function domainKey(): string;
}
