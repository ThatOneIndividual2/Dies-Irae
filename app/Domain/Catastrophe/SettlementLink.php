<?php

namespace App\Domain\Catastrophe;

final class SettlementLink
{
    public int $worldId;
    public string $fromId;
    public string $toId;
    public string $vector;
    public int $intensity;
    public bool $active;
    public ?string $armyId;

    public function __construct(
        int $worldId,
        string $fromId,
        string $toId,
        string $vector,
        int $intensity,
        bool $active = true,
        ?string $armyId = null
    ) {
        $this->worldId = $worldId;
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->vector = $vector;
        $this->intensity = max(0, min(10000, $intensity));
        $this->active = $active;
        $this->armyId = $armyId;
    }
}
