<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\Army;

final class AssignCommander
{
    public function execute(Army $army, Commander $commander): Army
    {
        $army->commander = $commander;

        return $army;
    }
}
