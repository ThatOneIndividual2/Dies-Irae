<?php

namespace App\Domain\Warfare\Ports;

final class NullCatastrophePort implements CatastrophePort
{
    public function plagueIntensity(int $worldId, int $territoryId): int
    {
        return 0;
    }

    public function settlementMorale(int $worldId, int $territoryId): int
    {
        return 50;
    }

    public function campFeverRiskFromCorpses(int $corpses): int
    {
        if ($corpses <= 0) {
            return 0;
        }

        return min(25, (int) floor($corpses / 200));
    }
}
