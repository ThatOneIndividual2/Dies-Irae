<?php

namespace App\Domain\Faith;

final class FaithService implements FaithContract
{
    public function domainKey(): string
    {
        return 'faith';
    }
}
