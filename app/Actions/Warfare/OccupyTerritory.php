<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\OccupationResult;
use App\Domain\Warfare\State\War;

final class OccupyTerritory
{
    public function __construct(private WarDirector $director)
    {
    }

    public function execute(War $war, int $territoryId, int $occupierBelligerentId): OccupationResult
    {
        return $this->director->occupy($war, $territoryId, $occupierBelligerentId);
    }
}
