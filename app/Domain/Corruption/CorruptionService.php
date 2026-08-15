<?php

namespace App\Domain\Corruption;

final class CorruptionService implements CorruptionContract
{
    public function domainKey(): string
    {
        return 'corruption';
    }
}
