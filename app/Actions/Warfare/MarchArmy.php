<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\MovementResult;

final class MarchArmy
{
    public function __construct(private WarDirector $director)
    {
    }

    public function execute(Army $army, int $toTerritoryId): MovementResult
    {
        $force = ForceProfile::forNature($army->nature);

        return $this->director->movement->march($army, $force, $toTerritoryId);
    }
}
