<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Enums\RuinState;
use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Enums\UnitCategory;
use App\Domain\Warfare\Ports\HoldingLevySnapshot;
use App\Domain\Warfare\Ports\LevySource;
use App\Domain\Warfare\Ports\PopulationPort;
use App\Domain\Warfare\State\UnitStack;

final class LevyService
{
    public function __construct(
        private LevySource $source,
        private PopulationPort $population,
        private WarfareBalance $balance,
    ) {
    }

    /**
     * @return UnitStack[]
     */
    public function raise(int $worldId, int $belligerentId, string $belligerentKind, ForceProfile $force): array
    {
        if ($force->usesHolyOrderCall) {
            return $this->raiseHolyOrder($worldId, $belligerentId, $belligerentKind);
        }
        if ($force->usesForcedLevies) {
            return $this->raiseForced($worldId, $belligerentId, $belligerentKind, $force);
        }
        if (!$force->usesFeudalLevies) {
            return [];
        }

        return $this->raiseFeudal($worldId, $belligerentId, $belligerentKind);
    }

    /**
     * Donor formula: floor(baseLevy * levyRate / 100), then population collapse.
     *
     * @return UnitStack[]
     */
    public function raiseFeudal(int $worldId, int $belligerentId, string $belligerentKind): array
    {
        $men = 0;
        foreach ($this->source->holdingsForBelligerent($worldId, $belligerentId, $belligerentKind) as $holding) {
            $men += $this->holdingLevy($holding);
        }

        if ($men <= 0) {
            return [];
        }

        return [new UnitStack(UnitCategory::LEVY, $men, 1.0, 'feudal levies')];
    }

    public function holdingLevy(HoldingLevySnapshot $holding): int
    {
        if (!RuinState::raisesLevy($holding->ruinState)) {
            return 0;
        }

        $due = $this->balance->contractLevyDue($holding->baseLevy, $holding->contractLevyRate);
        $baseline = max(1, $holding->baselinePopulation);
        $popFactor = max(0.0, min(1.0, $holding->currentPopulation / $baseline));
        $eligible = $this->population->levyEligibleFraction($holding->worldId, $holding->territoryId);

        return (int) floor($due * $popFactor * $eligible);
    }

    /**
     * @return UnitStack[]
     */
    private function raiseHolyOrder(int $worldId, int $belligerentId, string $belligerentKind): array
    {
        $sergeants = 0;
        $knights = 0;
        foreach ($this->source->holdingsForBelligerent($worldId, $belligerentId, $belligerentKind) as $holding) {
            $base = $this->holdingLevy($holding);
            $knights += (int) floor($base * 0.25);
            $sergeants += (int) floor($base * 0.75);
        }

        $stacks = [];
        if ($sergeants > 0) {
            $stacks[] = new UnitStack(UnitCategory::CONSECRATED, $sergeants, 1.2, 'order sergeants', true);
        }
        if ($knights > 0) {
            $stacks[] = new UnitStack(UnitCategory::CONSECRATED, $knights, 1.6, 'order knights', true);
        }

        return $stacks;
    }

    /**
     * @return UnitStack[]
     */
    private function raiseForced(int $worldId, int $belligerentId, string $belligerentKind, ForceProfile $force): array
    {
        $men = 0;
        foreach ($this->source->holdingsForBelligerent($worldId, $belligerentId, $belligerentKind) as $holding) {
            $men += (int) floor($this->holdingLevy($holding) * 1.15);
        }
        if ($men <= 0) {
            return [];
        }

        $category = $force->nature === \App\Domain\Warfare\Enums\ArmyNature::CULT
            ? UnitCategory::CULTIST
            : UnitCategory::CORRUPTED;

        return [new UnitStack($category, $men, 0.85, 'forced host')];
    }
}
