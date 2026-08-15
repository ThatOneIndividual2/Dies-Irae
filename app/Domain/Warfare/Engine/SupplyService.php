<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Ports\HellPort;
use App\Domain\Warfare\Ports\PopulationPort;
use App\Domain\Warfare\State\Army;

final class SupplyService
{
    public function __construct(
        private WarfareBalance $balance,
        private HellPort $hell,
        private PopulationPort $population,
    ) {
    }

    public function tick(Army $army, ForceProfile $force): Army
    {
        if ($force->requiresPortal) {
            return $this->tickPortal($army, $force);
        }

        if ($force->supplyKind === \App\Domain\Warfare\Enums\SupplyKind::PLUNDER) {
            $army->supply = Army::clamp($army->supply + $this->balance->dailyPlunderGain - 2);
            $army->starving = false;

            return $army;
        }

        if ($force->supplyKind === \App\Domain\Warfare\Enums\SupplyKind::DEVOUR) {
            $pop = $this->population->population($army->worldId, $army->territoryId);
            $drain = $this->balance->dailyDevourDrain;
            if ($pop < 200) {
                $army->supply = Army::clamp($army->supply - $drain);
            } else {
                $army->supply = Army::clamp($army->supply + 3);
            }
            $army->starving = $force->starvable && $army->supply < $this->balance->starveSupplyThreshold;

            return $army;
        }

        $drain = $force->supplyKind === \App\Domain\Warfare\Enums\SupplyKind::TITHE
            ? $this->balance->dailyTitheDrain
            : $this->balance->dailyFoodDrain;

        $army->supply = Army::clamp($army->supply - $drain);
        $army->starving = $force->starvable && $army->supply < $this->balance->starveSupplyThreshold;
        if ($army->starving) {
            $army->morale = Army::clamp($army->morale - 6);
        }

        return $army;
    }

    private function tickPortal(Army $army, ForceProfile $force): Army
    {
        $portal = $this->hell->nearestPortal($army->worldId, $army->territoryId, $army->belligerentId);
        if ($portal === null || !$portal->open) {
            $army->manifestationRemaining = max(0, $army->manifestationRemaining - 80);
            $army->supply = 0;
            $army->starving = false;
            if ($army->manifestationRemaining <= 0) {
                $army->replaceStacks([]);
                $army->morale = 0;
            }

            return $army;
        }

        $army->portalTerritoryId = $portal->territoryId;
        $army->supply = Army::clamp(50 + (int) floor($portal->strength / 4));
        $army->starving = false;
        $army->manifestationRemaining = min(
            $force->manifestationCap,
            $army->manifestationRemaining + (int) floor($portal->strength / 5)
        );

        return $army;
    }
}
