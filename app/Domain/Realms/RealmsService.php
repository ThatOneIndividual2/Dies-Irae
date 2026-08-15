<?php

namespace App\Domain\Realms;

final class RealmsService implements RealmsContract
{
    public function domainKey(): string
    {
        return 'realms';
    }
}
