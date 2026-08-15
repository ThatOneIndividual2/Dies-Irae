<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\BattleResult;

final class ResolveBattle
{
    public function __construct(private WarDirector $director)
    {
    }

    public function execute(Army $attacker, Army $defender, bool $sacked = false): BattleResult
    {
        return $this->director->fight($attacker, $defender, $sacked);
    }
}
