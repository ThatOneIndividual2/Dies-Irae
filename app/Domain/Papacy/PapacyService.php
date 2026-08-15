<?php

namespace App\Domain\Papacy;

final class PapacyService implements PapacyContract
{
    public function domainKey(): string
    {
        return 'papacy';
    }
}
