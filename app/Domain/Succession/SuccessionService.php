<?php

namespace App\Domain\Succession;

final class SuccessionService implements SuccessionContract
{
    public function domainKey(): string
    {
        return 'succession';
    }
}
