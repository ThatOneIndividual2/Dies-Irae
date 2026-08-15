<?php

namespace App\Domain\Population\Ports;

final class RecordingArmyAttritionPort implements ArmyAttritionPort
{
    /** @var list<array<string,mixed>> */
    public array $events = [];

    public function applyLevyLoss(int $worldId, string $settlementId, int $lostLevy, string $cause): void
    {
        $this->events[] = [
            'world_id' => $worldId,
            'settlement_id' => $settlementId,
            'lost_levy' => $lostLevy,
            'cause' => $cause,
        ];
    }
}
