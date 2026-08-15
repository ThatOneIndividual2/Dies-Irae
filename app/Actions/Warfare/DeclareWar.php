<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\War;
use App\Domain\Warfare\State\WarGoal;

final class DeclareWar
{
    public function __construct(private WarDirector $director)
    {
    }

    public function execute(Belligerent $aggressor, Belligerent $defender, WarGoal $goal, string $startedOn): War
    {
        return $this->director->declareWar($aggressor, $defender, $goal, $startedOn);
    }
}
