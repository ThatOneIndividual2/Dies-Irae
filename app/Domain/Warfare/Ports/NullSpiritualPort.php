<?php

namespace App\Domain\Warfare\Ports;

final class NullSpiritualPort implements SpiritualPort
{
    public function clergySupport(int $worldId, int $territoryId, int $belligerentId): int
    {
        return 0;
    }

    public function relicSupport(int $worldId, int $territoryId, int $belligerentId): int
    {
        return 0;
    }

    public function localFaith(int $worldId, int $territoryId): int
    {
        return 50;
    }

    public function battlefieldSanctity(int $worldId, int $territoryId): string
    {
        return 'ordinary';
    }

    public function consecratedUnitBonus(int $worldId, int $armyId): int
    {
        return 0;
    }
}
