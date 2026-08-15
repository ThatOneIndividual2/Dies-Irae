<?php

namespace App\Domain\Warfare\Support;

use App\Domain\Enums\RuinState;
use App\Domain\Warfare\Ports\CatastrophePort;
use App\Domain\Warfare\Ports\GeographyPort;
use App\Domain\Warfare\Ports\HellPort;
use App\Domain\Warfare\Ports\HoldingLevySnapshot;
use App\Domain\Warfare\Ports\LevySource;
use App\Domain\Warfare\Ports\PopulationPort;
use App\Domain\Warfare\Ports\PortalSnapshot;
use App\Domain\Warfare\Ports\SpiritualPort;
use App\Domain\Warfare\Ports\TerritorySnapshot;

/**
 * In-memory geography, levies, and optional supernatural reads for tests and early wiring.
 */
final class InMemoryWarfareWorld implements LevySource, GeographyPort, PopulationPort, SpiritualPort, CatastrophePort, HellPort
{
    public int $worldId = 1;

    /** @var array<int, TerritorySnapshot> */
    public array $territories = [];

    /** @var array<int, HoldingLevySnapshot> */
    public array $holdings = [];

    /** @var array<int, array{population:int, baseline:int}> */
    public array $pops = [];

    /** @var array<string, int> */
    public array $clergy = [];

    /** @var array<string, int> */
    public array $relics = [];

    /** @var array<int, int> */
    public array $faith = [];

    /** @var array<int, int> */
    public array $plague = [];

    /** @var array<int, int> */
    public array $corruption = [];

    /** @var array<int, PortalSnapshot> */
    public array $portals = [];

    public int $clergyReads = 0;

    public int $relicReads = 0;

    public int $plagueReads = 0;

    public int $corruptionReads = 0;

    public static function europeSample(): self
    {
        $w = new self();
        $w->addTerritory(1, 'Orleans', 'plains', 10, 10, 3, [2, 4], 2);
        $w->addTerritory(2, 'Blois', 'plains', 11, 11, 2, [1, 3], 2);
        $w->addTerritory(3, 'Tours', 'hills', 11, 11, 4, [2], 3);
        $w->addTerritory(4, 'Chartres', 'plains', 10, 10, 2, [1], 2);
        $w->addTerritory(5, 'Rift Vale', 'blight', 99, 10, 1, [4], 2);

        $w->addHolding(1, 1, 10, 80, 40, 8000, 8000);
        $w->addHolding(2, 2, 11, 60, 50, 5000, 5000);
        $w->addHolding(3, 3, 11, 40, 50, 4000, 4000);
        $w->addHolding(4, 4, 10, 50, 40, 6000, 6000);
        $w->addHolding(5, 5, 99, 20, 10, 1200, 4000);
        $w->addHolding(6, 4, 20, 70, 100, 3000, 3000);
        $w->addHolding(7, 2, 30, 40, 80, 2500, 2500);
        $w->addHolding(8, 3, 40, 50, 90, 3500, 3500);

        $w->portals[5] = new PortalSnapshot(1, 5, 99, 80, true);

        return $w;
    }

    public function addTerritory(
        int $id,
        string $name,
        string $terrain,
        int $controller,
        int $owner,
        int $fort,
        array $neighbors,
        int $days,
    ): void {
        $this->territories[$id] = new TerritorySnapshot(
            $this->worldId,
            $id,
            $name,
            $terrain,
            $controller,
            $owner,
            $fort,
            $neighbors,
            $days,
        );
        $this->pops[$id] = ['population' => 5000, 'baseline' => 5000];
        $this->faith[$id] = 50;
        $this->plague[$id] = 0;
        $this->corruption[$id] = 0;
    }

    public function addHolding(
        int $id,
        int $territoryId,
        int $owner,
        int $baseLevy,
        int $rate,
        int $pop,
        int $baseline,
    ): void {
        $this->holdings[$id] = new HoldingLevySnapshot(
            $this->worldId,
            $id,
            $territoryId,
            $owner,
            $baseLevy,
            $rate,
            $pop,
            $baseline,
            RuinState::FUNCTIONING,
            $this->territories[$territoryId]->fortification,
            false,
        );
        $this->pops[$territoryId] = ['population' => $pop, 'baseline' => $baseline];
    }

    public function holdingsForBelligerent(int $worldId, int $belligerentId, string $belligerentKind): array
    {
        return array_values(array_filter(
            $this->holdings,
            fn (HoldingLevySnapshot $h) => $h->worldId === $worldId && $h->ownerCharacterId === $belligerentId
        ));
    }

    public function territory(int $worldId, int $territoryId): TerritorySnapshot
    {
        return $this->territories[$territoryId];
    }

    public function neighbors(int $worldId, int $territoryId): array
    {
        return $this->territories[$territoryId]->neighborIds;
    }

    public function movementDays(int $worldId, int $fromTerritoryId, int $toTerritoryId): int
    {
        return $this->territories[$toTerritoryId]->movementDays;
    }

    public function areNeighbors(int $worldId, int $a, int $b): bool
    {
        return in_array($b, $this->territories[$a]->neighborIds, true);
    }

    public function population(int $worldId, int $territoryId): int
    {
        return $this->pops[$territoryId]['population'];
    }

    public function baselinePopulation(int $worldId, int $territoryId): int
    {
        return $this->pops[$territoryId]['baseline'];
    }

    public function levyEligibleFraction(int $worldId, int $territoryId): float
    {
        return 1.0;
    }

    public function clergySupport(int $worldId, int $territoryId, int $belligerentId): int
    {
        $this->clergyReads++;

        return $this->clergy[$territoryId . ':' . $belligerentId] ?? 0;
    }

    public function relicSupport(int $worldId, int $territoryId, int $belligerentId): int
    {
        $this->relicReads++;

        return $this->relics[$territoryId . ':' . $belligerentId] ?? 0;
    }

    public function localFaith(int $worldId, int $territoryId): int
    {
        return $this->faith[$territoryId] ?? 50;
    }

    public function battlefieldSanctity(int $worldId, int $territoryId): string
    {
        return 'ordinary';
    }

    public function consecratedUnitBonus(int $worldId, int $armyId): int
    {
        return 0;
    }

    public function plagueIntensity(int $worldId, int $territoryId): int
    {
        $this->plagueReads++;

        return $this->plague[$territoryId] ?? 0;
    }

    public function settlementMorale(int $worldId, int $territoryId): int
    {
        return 50;
    }

    public function campFeverRiskFromCorpses(int $corpses): int
    {
        if ($corpses <= 0) {
            return 0;
        }

        return min(25, (int) floor($corpses / 200));
    }

    public function corruption(int $worldId, int $territoryId): int
    {
        $this->corruptionReads++;

        return $this->corruption[$territoryId] ?? 0;
    }

    public function nearestPortal(int $worldId, int $territoryId, int $factionId): ?PortalSnapshot
    {
        foreach ($this->portals as $portal) {
            if ($portal->factionId === $factionId && $portal->open) {
                return $portal;
            }
        }

        return null;
    }

    public function manifestationCap(int $worldId, int $factionId): int
    {
        return 4000;
    }

    public function hostileSupernaturalReads(): void
    {
        foreach ($this->territories as $t) {
            $this->plague[$t->territoryId] = 90;
            $this->corruption[$t->territoryId] = 90;
            $this->clergy[$t->territoryId . ':10'] = 80;
            $this->relics[$t->territoryId . ':10'] = 80;
            $this->clergy[$t->territoryId . ':11'] = 80;
            $this->relics[$t->territoryId . ':11'] = 80;
        }
    }
}
