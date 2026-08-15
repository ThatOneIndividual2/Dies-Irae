<?php

namespace App\Domain\Faith;

/**
 * Domain contract for Faith. Bound by FaithServiceProvider.
 * Implementations must not import or query Feudalism.
 */
interface FaithContract
{
    public function domainKey(): string;
}
