<?php

namespace App\Domain\Catastrophe;

use App\Domain\Enums\PlagueHostType;
use App\Domain\Support\IntClamp;

/**
 * Infection at a host (settlement, army, caravan, pilgrim column).
 * Batches keep incubation and infectious duration without tracking persons.
 */
final class PlagueFocus
{
    public string $hostId;
    public string $hostType;
    public string $waveId;
    public int $heads;
    public int $incubating;
    public int $infectious;
    public int $recovered;
    public int $dead;
    public int $localSupernaturalBp;
    public ?string $locationSettlementId;

    /** @var list<array{remaining:int,souls:int}> */
    public array $incubationBatches = [];

    /** @var list<array{remaining:int,souls:int}> */
    public array $infectiousBatches = [];

    public function __construct(
        string $hostId,
        string $hostType,
        string $waveId,
        int $heads,
        ?string $locationSettlementId = null,
        int $localSupernaturalBp = 10000
    ) {
        $this->hostId = $hostId;
        $this->hostType = $hostType;
        $this->waveId = $waveId;
        $this->heads = max(0, $heads);
        $this->incubating = 0;
        $this->infectious = 0;
        $this->recovered = 0;
        $this->dead = 0;
        $this->localSupernaturalBp = max(0, $localSupernaturalBp);
        $this->locationSettlementId = $locationSettlementId;
    }

    public static function forSettlement(string $settlementId, string $waveId, int $souls, int $localSupernaturalBp = 10000): self
    {
        return new self($settlementId, PlagueHostType::SETTLEMENT, $waveId, $souls, $settlementId, $localSupernaturalBp);
    }

    public static function army(string $armyId, string $waveId, int $heads, string $locationSettlementId, int $localSupernaturalBp = 10000): self
    {
        return new self($armyId, PlagueHostType::ARMY, $waveId, $heads, $locationSettlementId, $localSupernaturalBp);
    }

    public function prevalenceBp(): int
    {
        if ($this->heads <= 0) {
            return 0;
        }

        return IntClamp::between(intdiv(($this->infectious + $this->incubating) * 10000, $this->heads), 0, 10000);
    }

    public function susceptible(): int
    {
        return IntClamp::nonNegative($this->heads - $this->incubating - $this->infectious - $this->recovered);
    }

    public function addIncubating(int $souls, int $incubationTicks): void
    {
        $souls = max(0, $souls);
        if ($souls === 0) {
            return;
        }
        $this->incubationBatches[] = ['remaining' => $incubationTicks, 'souls' => $souls];
        $this->incubating += $souls;
    }

    public function addInfectious(int $souls, int $infectiousTicks): void
    {
        $souls = max(0, $souls);
        if ($souls === 0) {
            return;
        }
        $this->infectiousBatches[] = ['remaining' => $infectiousTicks, 'souls' => $souls];
        $this->infectious += $souls;
    }

    public function syncCounts(): void
    {
        $this->incubating = 0;
        foreach ($this->incubationBatches as $batch) {
            $this->incubating += $batch['souls'];
        }
        $this->infectious = 0;
        foreach ($this->infectiousBatches as $batch) {
            $this->infectious += $batch['souls'];
        }
        $this->heads = max(0, $this->heads);
        $this->recovered = min($this->recovered, $this->heads);
    }
}
