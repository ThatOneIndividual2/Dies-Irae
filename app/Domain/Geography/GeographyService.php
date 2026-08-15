<?php

namespace App\Domain\Geography;

final class GeographyService implements GeographyContract
{
    public function domainKey(): string
    {
        return 'geography';
    }
}
