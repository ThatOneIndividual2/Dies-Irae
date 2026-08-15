<?php

namespace App\Domain\Heresy;

final class HeresyService implements HeresyContract
{
    public function domainKey(): string
    {
        return 'heresy';
    }
}
