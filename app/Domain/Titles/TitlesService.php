<?php

namespace App\Domain\Titles;

final class TitlesService implements TitlesContract
{
    public function domainKey(): string
    {
        return 'titles';
    }
}
