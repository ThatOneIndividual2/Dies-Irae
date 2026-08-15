<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\War;

final class FormArmy
{
    public function __construct(private WarDirector $director)
    {
    }

    /**
     * @param \App\Domain\Warfare\State\UnitStack[] $menAtArms
     */
    public function execute(
        War $war,
        Belligerent $belligerent,
        int $territoryId,
        array $menAtArms,
        ?Commander $commander = null,
        ?int $portalTerritoryId = null,
    ): Army {
        return $this->director->formArmy($war, $belligerent, $territoryId, $menAtArms, $commander, $portalTerritoryId);
    }
}
