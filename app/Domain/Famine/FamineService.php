<?php

namespace App\Domain\Famine;

final class FamineService implements FamineContract
{
    public function domainKey(): string
    {
        return 'famine';
    }
}
