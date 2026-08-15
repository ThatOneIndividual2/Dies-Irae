<?php

namespace App\Domain\Economy;

final class EconomyService implements EconomyContract
{
    public function domainKey(): string
    {
        return 'economy';
    }
}
