<?php

namespace App\Domain\Dynasties;

final class DynastiesService implements DynastiesContract
{
    public function domainKey(): string
    {
        return 'dynasties';
    }
}
