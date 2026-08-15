<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\Siege;
use App\Domain\Warfare\State\War;

final class TickSiege
{
    public function __construct(private WarDirector $director)
    {
    }

    public function open(Army $army, int $fortification, int $garrison): Siege
    {
        $war = $this->director->wars[$army->warId];
        $force = ForceProfile::forNature($army->nature);

        return $this->director->sieges->open($army, $force, $this->director->warProfile($war), $fortification, $garrison);
    }

    public function execute(Siege $siege, Army $army): Siege
    {
        $war = $this->director->wars[$army->warId];
        $force = ForceProfile::forNature($army->nature);

        return $this->director->sieges->tick($siege, $army, $force, $this->director->warProfile($war));
    }
}
