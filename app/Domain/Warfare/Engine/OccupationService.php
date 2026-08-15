<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\Enums\OccupationMode;
use App\Domain\Warfare\Ports\GeographyPort;
use App\Domain\Warfare\State\OccupationResult;
use App\Domain\Warfare\State\War;

final class OccupationService
{
    public function __construct(private GeographyPort $geography)
    {
    }

    public function occupy(War $war, WarProfile $profile, int $territoryId, int $occupierBelligerentId): OccupationResult
    {
        $territory = $this->geography->territory($war->worldId, $territoryId);
        $owner = $territory->ownerBelligerentId;
        $controller = $territory->controllerBelligerentId;

        $military = false;
        $legal = false;
        $overlay = false;
        $infiltration = false;
        $corruption = false;

        if ($profile->resilientToOccupationRules) {
            $overlay = $profile->appliesHellOverlay;
            $controller = $territory->controllerBelligerentId;
            $owner = $territory->ownerBelligerentId;
            $war->overlayTerritoryIds[] = $territoryId;
            $war->overlayTerritoryIds = array_values(array_unique($war->overlayTerritoryIds));
        } elseif ($profile->occupationMode === OccupationMode::INFILTRATION) {
            $infiltration = true;
            $war->infiltratedTerritoryIds[] = $territoryId;
            $war->infiltratedTerritoryIds = array_values(array_unique($war->infiltratedTerritoryIds));
        } else {
            $military = $profile->transfersMilitaryControl;
            $legal = $profile->transfersLegalOwnershipOnOccupy;
            if ($military) {
                $controller = $occupierBelligerentId;
                $war->occupiedTerritoryIds[] = $territoryId;
                $war->occupiedTerritoryIds = array_values(array_unique($war->occupiedTerritoryIds));
            }
            if ($legal) {
                $owner = $occupierBelligerentId;
            }
            if ($profile->occupationMode === OccupationMode::CORRUPT_CONTROL) {
                $corruption = true;
            }
            if ($profile->occupationMode === OccupationMode::COMMANDERY) {
                $military = true;
                $controller = $occupierBelligerentId;
            }
        }

        return new OccupationResult(
            $territoryId,
            $profile->occupationMode,
            $military,
            $legal,
            $overlay,
            $infiltration,
            $corruption,
            $controller,
            $owner,
        );
    }
}
