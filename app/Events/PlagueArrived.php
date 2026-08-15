<?php

namespace App\Events;

final class PlagueArrived
{
    public int $worldId;
    public string $settlementId;
    public string $waveId;
    public int $tick;

    public function __construct(int $worldId, string $settlementId, string $waveId, int $tick)
    {
        $this->worldId = $worldId;
        $this->settlementId = $settlementId;
        $this->waveId = $waveId;
        $this->tick = $tick;
    }
}
