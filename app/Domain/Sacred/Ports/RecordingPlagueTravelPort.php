<?php

namespace App\Domain\Sacred\Ports;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\SpreadVector;

final class RecordingPlagueTravelPort implements PlagueTravelPort
{
    /** @var list<array{world_id:int, from:string, to:string, intensity:int}> */
    public array $links = [];

    public function openPilgrimageLink(int $worldId, string $fromKey, string $toKey, int $intensity): void
    {
        $this->links[] = [
            'world_id' => $worldId,
            'from' => $fromKey,
            'to' => $toKey,
            'intensity' => $intensity,
        ];
    }

    public function openedLinks(): array
    {
        return $this->links;
    }

    public function applyToEngine(CatastropheEngine $engine): void
    {
        foreach ($this->links as $link) {
            if ((int) $engine->worldId !== (int) $link['world_id']) {
                continue;
            }
            $engine->link($link['from'], $link['to'], SpreadVector::PILGRIMAGE, (int) $link['intensity'], false);
        }
    }
}
