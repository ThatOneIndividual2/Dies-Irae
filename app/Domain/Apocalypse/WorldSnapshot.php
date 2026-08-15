<?php

namespace App\Domain\Apocalypse;

final class WorldSnapshot
{
    public string $phaseKey;

    public int $phaseOrdinal;

    public ApocalypseMeters $meters;

    public int $pressure;

    /** @var array<string, int> */
    public array $signalCounts;

    /** @var list<string> */
    public array $milestoneKeys;

    public int $daysInPhase;

    /**
     * @param  array<string, int>  $signalCounts
     * @param  list<string>  $milestoneKeys
     */
    public function __construct(
        string $phaseKey,
        int $phaseOrdinal,
        ApocalypseMeters $meters,
        int $pressure,
        array $signalCounts,
        array $milestoneKeys,
        int $daysInPhase = 0
    ) {
        $this->phaseKey = $phaseKey;
        $this->phaseOrdinal = $phaseOrdinal;
        $this->meters = $meters;
        $this->pressure = $pressure;
        $this->signalCounts = $signalCounts;
        $this->milestoneKeys = $milestoneKeys;
        $this->daysInPhase = $daysInPhase;
    }

    public function hasMilestone(string $key): bool
    {
        return in_array($key, $this->milestoneKeys, true);
    }

    public function signalCount(string $key): int
    {
        return (int) ($this->signalCounts[$key] ?? 0);
    }
}
