<?php

namespace App\Domain\Population\Ports;

interface NobleExtinctionPort
{
    public function recordLocalExtinction(int $worldId, string $settlementId, string $cause): void;
}
