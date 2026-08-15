<?php

namespace App\Domain\Population;

final class Displacement
{
    public int $worldId;
    public string $id;
    public string $fromId;
    public ?string $toId;
    public string $cause;
    public PopulationCohorts $cohorts;
    public int $carryingInfectious;
    public int $carryingIncubating;
    public string $status;
    public int $departedTick;
    public ?int $arrivedTick;

    public function __construct(
        int $worldId,
        string $id,
        string $fromId,
        ?string $toId,
        string $cause,
        PopulationCohorts $cohorts,
        int $carryingInfectious,
        int $carryingIncubating,
        int $departedTick,
        string $status = 'in_transit'
    ) {
        $this->worldId = $worldId;
        $this->id = $id;
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->cause = $cause;
        $this->cohorts = $cohorts;
        $this->carryingInfectious = max(0, $carryingInfectious);
        $this->carryingIncubating = max(0, $carryingIncubating);
        $this->status = $status;
        $this->departedTick = $departedTick;
        $this->arrivedTick = null;
    }

    public function souls(): int
    {
        return $this->cohorts->souls();
    }
}
