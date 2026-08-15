<?php

namespace App\Domain\Diplomacy;

final class DiplomacyService implements DiplomacyContract
{
    public function domainKey(): string
    {
        return 'diplomacy';
    }
}
