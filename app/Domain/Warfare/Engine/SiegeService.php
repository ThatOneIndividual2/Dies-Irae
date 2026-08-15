<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\Siege;

final class SiegeService
{
    private int $nextId = 1;

    public function __construct(private WarfareBalance $balance)
    {
    }

    public function open(Army $army, ForceProfile $force, WarProfile $war, int $fortification, int $garrison): Siege
    {
        $method = 'siege';
        if ($war->kind === \App\Domain\Warfare\Enums\WarfareKind::HUMAN_VS_DEMON && $force->corruptsTerrain) {
            $method = 'blight';
        }
        if ($war->kind === \App\Domain\Warfare\Enums\WarfareKind::HUMAN_VS_CULT) {
            $method = 'infiltration';
        }

        return new Siege(
            $army->worldId,
            $this->nextId++,
            $army->warId,
            $army->territoryId,
            $army->id,
            $fortification,
            $garrison,
            0,
            $method,
        );
    }

    public function tick(Siege $siege, Army $army, ForceProfile $force, WarProfile $war): Siege
    {
        if ($siege->fallen()) {
            $siege->fallen = true;

            return $siege;
        }

        $engines = $army->siegePower($this->balance);
        $commander = $army->commander ? $army->commander->martial : 0;
        $progress = 8 + (int) floor($engines * 2) + (int) floor($commander / 4) - $siege->fortification;

        if ($siege->method === 'blight') {
            $progress += 10 + (int) floor($force->fearAura / 5);
        }
        if ($siege->method === 'infiltration') {
            $progress += 6;
            $siege->garrison = max(0, $siege->garrison - 4);
        }

        if ($army->starving && $force->starvable) {
            $progress = (int) floor($progress * 0.6);
        }

        $siege->progress = min(100, max(0, $siege->progress + max(1, $progress)));
        $siege->garrison = max(0, $siege->garrison - 2);
        if ($siege->progress >= 100 || $siege->garrison <= 0) {
            $siege->fallen = true;
            $siege->progress = 100;
        }

        return $siege;
    }
}
