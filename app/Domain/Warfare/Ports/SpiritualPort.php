<?php

namespace App\Domain\Warfare\Ports;

/**
 * Optional Church-facing port. Human vs human warfare must remain valid
 * when every method returns zero / ordinary.
 */
interface SpiritualPort
{
    public function clergySupport(int $worldId, int $territoryId, int $belligerentId): int;

    public function relicSupport(int $worldId, int $territoryId, int $belligerentId): int;

    public function localFaith(int $worldId, int $territoryId): int;

    public function battlefieldSanctity(int $worldId, int $territoryId): string;

    public function consecratedUnitBonus(int $worldId, int $armyId): int;
}
