<?php

namespace App\Domain\SinVirtue;

final class SinVirtueService implements SinVirtueContract
{
    public function domainKey(): string
    {
        return 'sinvirtue';
    }
}
