<?php

namespace App\Domain\Population\Ports;

final class NullCharacterDeathPort implements CharacterDeathPort
{
    public function considerDeaths(int $worldId, string $settlementId, array $context): int
    {
        return 0;
    }
}
