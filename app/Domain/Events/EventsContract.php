<?php

namespace App\Domain\Events;

/**
 * Domain contract for Events. Bound by EventsServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface EventsContract
{
    public function domainKey(): string;
}
