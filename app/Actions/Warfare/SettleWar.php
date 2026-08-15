<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\War;

final class SettleWar
{
    public function __construct(private WarDirector $director)
    {
    }

    public function execute(War $war, int $winnerBelligerentId, string $settlementType): War
    {
        return $this->director->settle($war, $winnerBelligerentId, $settlementType);
    }
}
