<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Ports\GeographyPort;
use App\Domain\Warfare\Ports\HellPort;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\MovementResult;

final class MovementService
{
    public function __construct(
        private GeographyPort $geography,
        private SupplyService $supply,
        private HellPort $hell,
        private WarfareBalance $balance,
    ) {
    }

    public function march(Army $army, ForceProfile $force, int $toTerritoryId): MovementResult
    {
        $from = $army->territoryId;
        if (!$this->geography->areNeighbors($army->worldId, $from, $toTerritoryId)) {
            throw new \InvalidArgumentException('Armies may only march into neighboring territories.');
        }

        $days = $this->geography->movementDays($army->worldId, $from, $toTerritoryId);
        $blocked = false;
        $collapsed = false;
        $corruption = 0;

        if ($force->requiresPortal) {
            $portal = $this->hell->nearestPortal($army->worldId, $toTerritoryId, $army->belligerentId);
            if ($portal === null || !$portal->open) {
                $army->manifestationRemaining = max(0, $army->manifestationRemaining - (25 * $days));
                if ($army->manifestationRemaining <= 0) {
                    $army->replaceStacks([]);
                    $army->morale = 0;
                    $collapsed = true;
                    $blocked = true;
                }
            }
        }

        if (!$blocked) {
            $army->territoryId = $toTerritoryId;
            $army->daysInField += $days;
            for ($i = 0; $i < $days; $i++) {
                $this->supply->tick($army, $force);
            }
        }

        if ($force->corruptsTerrain && !$blocked) {
            $corruption = 4 + (int) floor($army->fear / 20);
        }

        $army->fear = Army::clamp($army->fear + (int) floor($force->fearAura / 8));

        return new MovementResult(
            $army->id,
            $from,
            $blocked ? $from : $toTerritoryId,
            $days,
            $army->supply,
            $army->starving,
            $blocked,
            $collapsed,
            $corruption,
        );
    }
}
