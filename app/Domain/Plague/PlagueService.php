<?php

namespace App\Domain\Plague;

final class PlagueService implements PlagueContract
{
    public function domainKey(): string
    {
        return 'plague';
    }
}
