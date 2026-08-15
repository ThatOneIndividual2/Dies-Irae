<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\UnitStack;

final class RaiseMenAtArms
{
    public function __construct(private WarDirector $director)
    {
    }

    /**
     * Standing troops. Donor analog: persistent infantry/cavalry/archers/siege, not User columns.
     *
     * @return UnitStack[]
     */
    public function execute(int $infantry, int $cavalry, int $archers, int $siege, bool $consecrated = false): array
    {
        return $this->director->armies->menAtArms($infantry, $cavalry, $archers, $siege, $consecrated);
    }
}
