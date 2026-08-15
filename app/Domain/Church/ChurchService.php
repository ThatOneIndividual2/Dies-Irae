<?php

namespace App\Domain\Church;

final class ChurchService implements ChurchContract
{
    public function __construct(private ChurchAuthority $authority)
    {
    }

    public function domainKey(): string
    {
        return 'church';
    }

    public function authority(): ChurchAuthority
    {
        return $this->authority;
    }
}
