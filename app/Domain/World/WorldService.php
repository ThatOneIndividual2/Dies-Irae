<?php

namespace App\Domain\World;

final class WorldService implements WorldContract
{
    public function domainKey(): string
    {
        return 'world';
    }
}
