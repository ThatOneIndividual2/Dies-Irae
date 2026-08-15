<?php

namespace App\Domain\Population;

use App\Domain\Enums\ClergyCare;
use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\QuarantineLevel;
use App\Domain\Enums\RuinState;
use App\Domain\Enums\SettlementKind;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;

final class Settlement
{
    public int $worldId;
    public string $id;
    public string $name;
    public string $kind;
    public PopulationCohorts $cohorts;
    public int $peakSouls;
    public int $morale;
    public int $despair;
    public int $corruption;
    public int $foodStores;
    public int $foodYieldPerWorker;
    public int $unburied;
    public int $graveyardCapacity;
    public string $ruinState;
    public string $ruinReason;
    public string $quarantine;
    public string $corpseHandling;
    public string $clergyCare;
    public int $ticksAbandoned;
    public int $incubating;
    public int $infectious;
    public int $recovered;
    public bool $hellOccupation;
    public ?int $territoryId;
    public ?int $holdingId;

    /** @var list<array{remaining:int,souls:int}> */
    public array $incubationBatches = [];

    /** @var list<array{remaining:int,souls:int}> */
    public array $infectiousBatches = [];

    public function __construct(
        int $worldId,
        string $id,
        string $name,
        string $kind,
        PopulationCohorts $cohorts,
        int $morale = 60,
        int $despair = 5,
        int $corruption = 0,
        int $foodStores = 0,
        int $foodYieldPerWorker = 2,
        ?int $graveyardCapacity = null,
        string $ruinState = RuinState::FUNCTIONING,
        string $quarantine = QuarantineLevel::NONE,
        string $corpseHandling = CorpseHandling::CONSECRATED,
        string $clergyCare = ClergyCare::PARISH,
        ?int $territoryId = null,
        ?int $holdingId = null
    ) {
        $this->worldId = $worldId;
        $this->id = $id;
        $this->name = $name;
        $this->kind = $kind;
        $this->cohorts = $cohorts;
        $this->peakSouls = max(1, $cohorts->souls());
        $this->morale = IntClamp::between($morale, 0, 100);
        $this->despair = IntClamp::between($despair, 0, 100);
        $this->corruption = IntClamp::between($corruption, 0, 100);
        $this->foodStores = IntClamp::nonNegative($foodStores);
        $this->foodYieldPerWorker = max(0, $foodYieldPerWorker);
        $this->unburied = 0;
        $this->graveyardCapacity = $graveyardCapacity ?? max(50, intdiv($this->peakSouls, 4));
        $this->ruinState = $ruinState;
        $this->ruinReason = 'found';
        $this->quarantine = $quarantine;
        $this->corpseHandling = $corpseHandling;
        $this->clergyCare = $clergyCare;
        $this->ticksAbandoned = 0;
        $this->incubating = 0;
        $this->infectious = 0;
        $this->recovered = 0;
        $this->hellOccupation = false;
        $this->territoryId = $territoryId;
        $this->holdingId = $holdingId;
    }

    public static function found(
        int $worldId,
        string $id,
        string $name,
        string $kind,
        PopulationCohorts $cohorts
    ): self {
        $food = $cohorts->foodDemand() * 14;

        return new self($worldId, $id, $name, $kind, $cohorts, 60, 5, 0, $food);
    }

    public function souls(): int
    {
        return $this->cohorts->souls();
    }

    public function workforce(): int
    {
        return $this->cohorts->workforce();
    }

    public function militaryAge(): int
    {
        return $this->cohorts->militaryAge();
    }

    public function foodDemand(): int
    {
        return $this->cohorts->foodDemand();
    }

    public function ruinModifiers(): RuinModifiers
    {
        return new RuinModifiers($this->ruinState);
    }

    public function foodProduction(): int
    {
        $base = $this->workforce() * $this->foodYieldPerWorker;
        $produced = $this->ruinModifiers()->applyFood($base);
        $sickDrag = BasisPoints::of($produced, $this->diseaseBurden());

        return IntClamp::nonNegative($produced - intdiv($sickDrag, 2));
    }

    public function foodDeficitBp(): int
    {
        $demand = $this->foodDemand();
        if ($demand <= 0) {
            return 0;
        }
        $available = $this->foodProduction() + $this->foodStores;
        if ($available >= $demand) {
            return 0;
        }

        return intdiv(($demand - $available) * 10000, $demand);
    }

    public function taxBase(): int
    {
        $raw = $this->cohorts->burghers * 3 + $this->cohorts->peasants + $this->cohorts->nobles * 8;

        return $this->ruinModifiers()->applyTax($raw);
    }

    public function levyBase(): int
    {
        return $this->ruinModifiers()->applyLevy($this->militaryAge());
    }

    public function diseaseBurden(): int
    {
        $souls = $this->souls();
        if ($souls <= 0) {
            return $this->infectious > 0 ? 10000 : 0;
        }

        return IntClamp::between(intdiv(($this->infectious + $this->incubating) * 10000, $souls), 0, 10000);
    }

    public function corpsePressure(): int
    {
        if ($this->graveyardCapacity <= 0) {
            return $this->unburied > 0 ? 100 : 0;
        }

        return IntClamp::between(intdiv($this->unburied * 100, $this->graveyardCapacity), 0, 100);
    }

    public function migrationPressure(): int
    {
        $mods = $this->ruinModifiers();
        $pressure = intdiv($this->despair * 40, 100)
            + intdiv($this->diseaseBurden(), 250)
            + intdiv($this->foodDeficitBp(), 500)
            + intdiv($mods->migrationPush, 100)
            + intdiv($this->corpsePressure(), 5);

        if ($this->quarantine === QuarantineLevel::CORDON) {
            $pressure = intdiv($pressure * 4, 10);
        }

        return IntClamp::between($pressure, 0, 100);
    }

    public function rebellionPressure(): int
    {
        $mods = $this->ruinModifiers();
        $pressure = intdiv($mods->rebellion, 100)
            + intdiv($this->despair, 4)
            + ($this->cohorts->nobles === 0 && $this->souls() > 0 ? 20 : 0)
            - intdiv($this->morale, 5);

        return IntClamp::between($pressure, 0, 100);
    }

    public function isViable(): bool
    {
        return $this->souls() >= RuinStateMachine::VIABILITY_FLOOR
            && !in_array($this->ruinState, [
                RuinState::ABANDONED,
                RuinState::RUINED,
                RuinState::OVERRUN,
            ], true)
            && $this->workforce() >= 10;
    }

    public function contactIntensity(): int
    {
        return SettlementKind::contactIntensity($this->kind);
    }

    public function susceptible(): int
    {
        return IntClamp::nonNegative($this->souls() - $this->incubating - $this->infectious - $this->recovered);
    }

    public function syncBurdenFromBatches(): void
    {
        $this->incubating = 0;
        foreach ($this->incubationBatches as $batch) {
            $this->incubating += $batch['souls'];
        }
        $this->infectious = 0;
        foreach ($this->infectiousBatches as $batch) {
            $this->infectious += $batch['souls'];
        }
        $this->recovered = min($this->recovered, $this->souls());
    }

    public function census(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'souls' => $this->souls(),
            'class_composition' => $this->cohorts->toArray(),
            'clergy_population' => $this->cohorts->clergy,
            'military_age' => $this->militaryAge(),
            'workforce' => $this->workforce(),
            'food_demand' => $this->foodDemand(),
            'food_production' => $this->foodProduction(),
            'food_stores' => $this->foodStores,
            'disease_burden' => $this->diseaseBurden(),
            'morale' => $this->morale,
            'despair' => $this->despair,
            'corruption' => $this->corruption,
            'migration_pressure' => $this->migrationPressure(),
            'rebellion_pressure' => $this->rebellionPressure(),
            'tax_base' => $this->taxBase(),
            'levy_base' => $this->levyBase(),
            'corpse_pressure' => $this->corpsePressure(),
            'unburied' => $this->unburied,
            'graveyard_capacity' => $this->graveyardCapacity,
            'ruin_state' => $this->ruinState,
            'ruin_reason' => $this->ruinReason,
            'viable' => $this->isViable(),
            'quarantine' => $this->quarantine,
            'corpse_handling' => $this->corpseHandling,
            'clergy_care' => $this->clergyCare,
            'incubating' => $this->incubating,
            'infectious' => $this->infectious,
            'recovered' => $this->recovered,
            'prevalence' => $this->diseaseBurden(),
        ];
    }
}
