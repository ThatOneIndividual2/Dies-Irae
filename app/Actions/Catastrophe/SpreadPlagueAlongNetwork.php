<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\SpreadVector;

final class SpreadPlagueAlongNetwork
{
    /**
     * One explicit network pulse without a full demographic tick.
     * Used by scheduled `territory_plague_tick` handlers.
     */
    public function execute(CatastropheEngine $engine): int
    {
        $events = 0;
        foreach ($engine->links as $link) {
            if (!$link->active) {
                continue;
            }
            $from = $engine->settlements[$link->fromId] ?? null;
            $to = $engine->settlements[$link->toId] ?? null;
            if ($from === null || $to === null) {
                continue;
            }
            $exported = $engine->plagueTick->exportAlongLink($from, $to, $link, $engine->profile);
            if ($exported > 0) {
                $events++;
            }
        }

        return $events;
    }

    public function vectors(): array
    {
        return SpreadVector::all();
    }
}
