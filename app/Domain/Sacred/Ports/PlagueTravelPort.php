<?php

namespace App\Domain\Sacred\Ports;

interface PlagueTravelPort
{
    /**
     * Open a pilgrimage spread vector between two settlement keys.
     * Catastrophe owns the plague math. This domain only announces traffic.
     */
    public function openPilgrimageLink(int $worldId, string $fromKey, string $toKey, int $intensity): void;

    /**
     * @return list<array{world_id:int, from:string, to:string, intensity:int}>
     */
    public function openedLinks(): array;
}
