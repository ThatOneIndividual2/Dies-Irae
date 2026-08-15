<?php

namespace App\Domain\Characters;

final class CharactersService implements CharactersContract
{
    public function domainKey(): string
    {
        return 'characters';
    }
}
