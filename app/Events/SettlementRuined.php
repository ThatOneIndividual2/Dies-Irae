<?php

namespace App\Events;

final class SettlementRuined
{
    public int $worldId;
    public string $settlementId;
    public string $fromState;
    public string $toState;
    public string $reason;
    public int $tick;

    public function __construct(
        int $worldId,
        string $settlementId,
        string $fromState,
        string $toState,
        string $reason,
        int $tick
    ) {
        $this->worldId = $worldId;
        $this->settlementId = $settlementId;
        $this->fromState = $fromState;
        $this->toState = $toState;
        $this->reason = $reason;
        $this->tick = $tick;
    }
}
