<?php

namespace App\Domain\Population;

final class PopulationService implements PopulationContract
{
    public function domainKey(): string
    {
        return 'population';
    }
}
