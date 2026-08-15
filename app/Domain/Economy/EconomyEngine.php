<?php

namespace App\Domain\Economy;

use App\Domain\Enums\DisplacementCause;
use App\Domain\Enums\GrainMoveMode;
use App\Domain\Enums\SeasonPhase;
use App\Domain\Famine\FamineEngine;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Population\Displacement;
use App\Domain\Population\RefugeeFlow;
use App\Domain\Population\Settlement;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;
use App\Domain\Support\WorldBoundary;
use InvalidArgumentException;

/**
 * Strategic medieval economy. Settlements, food, dues, and famine share one clock.
 */
final class EconomyEngine
{
    public int $worldId;
    public SeasonCalendar $calendar;
    public int $tick = 0;

    /** @var array<string, EconomicSite> */
    public array $sites = [];

    /** @var TradeLane[] */
    public array $lanes = [];

    /** @var FieldForager[] */
    public array $armies = [];

    /** @var GrainShipment[] */
    public array $movements = [];

    /** @var Displacement[] */
    public array $displacements = [];

    /** @var list<array<string,mixed>> */
    public array $seasonLog = [];

    public HarvestCycle $harvest;
    public FamineEngine $famine;
    public GrainLogistics $grain;
    public DuesCollector $dues;
    public RefugeeFlow $refugees;
    public ApplyMassMortality $mortality;

    private int $moveSeq = 0;
    private int $flowSeq = 0;

    public function __construct(int $worldId, int $year = 1348, int $month = 9)
    {
        $this->worldId = $worldId;
        $this->calendar = new SeasonCalendar($year, $month);
        $this->harvest = new HarvestCycle();
        $this->mortality = new ApplyMassMortality();
        $this->famine = new FamineEngine($this->mortality);
        $this->grain = new GrainLogistics();
        $this->dues = new DuesCollector();
        $this->refugees = new RefugeeFlow();
    }

    public function addSettlement(Settlement $settlement): EconomicSite
    {
        WorldBoundary::assertSameWorld($this->worldId, $settlement->worldId, 'economy.add');
        $site = new EconomicSite($settlement);
        $this->sites[$settlement->id] = $site;

        return $site;
    }

    public function site(string $id): EconomicSite
    {
        if (!isset($this->sites[$id])) {
            throw new InvalidArgumentException("Unknown economic site {$id}");
        }

        return $this->sites[$id];
    }

    public function settlement(string $id): Settlement
    {
        return $this->site($id)->settlement;
    }

    public function link(string $fromId, string $toId, int $capacity = 800): TradeLane
    {
        $lane = new TradeLane($fromId, $toId, $capacity);
        $this->lanes[] = $lane;

        return $lane;
    }

    public function stationArmy(string $id, string $settlementId, int $men): FieldForager
    {
        $army = new FieldForager($id, $settlementId, $men);
        $this->armies[$id] = $army;

        return $army;
    }

    public function setWeather(int $bp): void
    {
        foreach ($this->sites as $site) {
            $site->weatherBp = $bp;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function runPhase(?string $phase = null): array
    {
        $phase = $phase ?? $this->calendar->phase;
        $this->calendar->advanceTo($phase);
        $results = [];
        foreach ($this->sites as $id => $site) {
            $results[$id] = $this->harvest->apply($site, $phase);
            if ($phase === SeasonPhase::HARVEST) {
                $results[$id]['dues'] = $this->dues->collect($site, $this->sites);
            }
            $this->refreshPrice($site);
        }
        $this->tick++;
        $entry = ['phase' => $phase, 'year' => $this->calendar->year, 'results' => $results];
        $this->seasonLog[] = $entry;

        return $entry;
    }

    public function advanceYear(): array
    {
        $log = [];
        foreach ([SeasonPhase::PLANTING, SeasonPhase::GROWING, SeasonPhase::HARVEST, SeasonPhase::WINTER] as $phase) {
            $log[] = $this->runPhase($phase);
        }

        return $log;
    }

    public function consumeDays(int $days): void
    {
        $days = max(0, $days);
        for ($i = 0; $i < $days; $i++) {
            $this->consumeOneDay();
        }
    }

    public function consumeOneDay(): void
    {
        foreach ($this->sites as $site) {
            $need = $site->dailyDemand();
            foreach ($this->armies as $army) {
                if ($army->settlementId === $site->id()) {
                    $need += $army->dailyRations();
                }
            }
            $taken = $site->takeCivicStores($need);
            if ($taken < $need && $site->monasteryStores > 0) {
                $fromHouse = $site->takeMonasteryStores($need - $taken);
                $taken += $fromHouse;
                if ($fromHouse > 0 && $site->isMonastery()) {
                    $site->churchLegitimacy = IntClamp::between($site->churchLegitimacy - 1, 0, 100);
                }
            }
            $short = $need - $taken;
            foreach ($this->armies as $army) {
                if ($army->settlementId !== $site->id() || $short <= 0 || $army->men <= 0) {
                    continue;
                }
                $desert = min($army->men, max(1, intdiv($army->men * $site->famine->militaryDesertion, 200)));
                $army->men -= $desert;
                $army->deserted += $desert;
            }
            $this->famine->evaluate($site, $this->armies);
            $this->famine->applyStarvation($site);
            $this->maybeFlee($site);
            $this->refreshPrice($site);
        }
        $this->tick++;
    }

    public function moveGrain(string $fromId, string $toId, int $amount, string $mode, string $actor = 'ruler'): GrainShipment
    {
        $this->moveSeq++;
        $shipment = $this->grain->move($this->sites, $this->lanes, $fromId, $toId, $amount, $mode, $actor, $this->moveSeq);
        $this->movements[] = $shipment;
        $this->famine->evaluate($this->site($fromId), $this->armies);
        $this->famine->evaluate($this->site($toId), $this->armies);

        return $shipment;
    }

    public function applyApocalypse(string $settlementId, string $effectKey): array
    {
        $effect = YieldCatalog::apocalypseEffect($effectKey);
        if ($effect === []) {
            throw new InvalidArgumentException("Unknown apocalypse economy effect {$effectKey}");
        }
        $site = $this->site($settlementId);
        if (isset($effect['blight_bp'])) {
            $site->blightBp = IntClamp::between($site->blightBp + $effect['blight_bp'], 0, 10000);
        }
        if (isset($effect['spoil_stores_bp'])) {
            $lost = $site->takeCivicStores(BasisPoints::of($site->civicStores(), $effect['spoil_stores_bp']));
            $effect['spoiled'] = $lost;
        }
        if (isset($effect['livestock_kill_bp'])) {
            $kill = BasisPoints::of($site->livestock, $effect['livestock_kill_bp']);
            $site->livestock = IntClamp::nonNegative($site->livestock - $kill);
            $effect['livestock_lost'] = $kill;
        }
        if (isset($effect['abandon_arable_bp'])) {
            $lost = BasisPoints::of($site->usableArable(), $effect['abandon_arable_bp']);
            $site->abandonedLand += $lost;
            $effect['arable_lost'] = $lost;
        }
        if (isset($effect['labor_penalty_bp'])) {
            $site->laborPenaltyBp = IntClamp::between($site->laborPenaltyBp + $effect['labor_penalty_bp'], 0, 10000);
        }
        if (!empty($effect['block_routes'])) {
            foreach ($this->lanes as $lane) {
                if ($lane->fromId === $settlementId || $lane->toId === $settlementId) {
                    $lane->blocked = true;
                }
            }
        }
        if (!empty($effect['haunt_routes'])) {
            foreach ($this->lanes as $lane) {
                if ($lane->fromId === $settlementId || $lane->toId === $settlementId) {
                    $lane->haunted = true;
                }
            }
        }

        return $effect;
    }

    public function raidMonastery(string $fromId, string $monasteryId, int $amount): GrainShipment
    {
        $house = $this->site($monasteryId);
        if (!$house->isMonastery()) {
            throw new InvalidArgumentException('Target is not a monastery');
        }
        $shipment = $this->moveGrain($monasteryId, $fromId, $amount, GrainMoveMode::SEIZURE, 'desperate_crowd');
        $house->churchLegitimacy = IntClamp::between($house->churchLegitimacy - 8, 0, 100);

        return $shipment;
    }

    public function assertNonNegative(): void
    {
        foreach ($this->sites as $site) {
            if ($site->settlement->foodStores < 0 || $site->monasteryStores < 0 || $site->livestock < 0) {
                throw new InvalidArgumentException('Negative economic stock');
            }
            foreach ($site->settlement->cohorts->toArray() as $n) {
                if ($n < 0) {
                    throw new InvalidArgumentException('Negative cohort');
                }
            }
        }
    }

    private function maybeFlee(EconomicSite $site): void
    {
        if (!$this->famine->shouldFlee($site)) {
            return;
        }
        $haven = $this->bestHaven($site);
        if ($haven === null) {
            return;
        }
        $count = $this->famine->fleeCount($site);
        if ($count <= 0) {
            return;
        }
        $this->flowSeq++;
        $flow = $this->refugees->depart(
            $site->settlement,
            $haven->settlement,
            $count,
            DisplacementCause::FAMINE,
            $this->tick,
            'famine-'.$this->flowSeq
        );
        $this->refugees->arrive($flow, $haven->settlement, $this->mortality);
        $this->displacements[] = $flow;
    }

    private function bestHaven(EconomicSite $from): ?EconomicSite
    {
        $best = null;
        $bestDays = -1;
        foreach ($this->sites as $site) {
            if ($site->id() === $from->id()) {
                continue;
            }
            $days = $site->dailyDemand() > 0 ? intdiv($site->totalFood(), $site->dailyDemand()) : 0;
            if ($days > $bestDays && $site->settlement->ruinModifiers()->acceptsRefugees) {
                $best = $site;
                $bestDays = $days;
            }
        }

        return $best;
    }

    private function refreshPrice(EconomicSite $site): void
    {
        $bump = intdiv($site->famine->deficitBp, 80) + $site->famine->unrest;
        $site->regionalPrice = IntClamp::between(YieldCatalog::BASE_PRICE + $bump, 40, 400);
        $site->marketActivity = IntClamp::between(
            $site->marketActivity - intdiv($site->famine->unrest, 20) + ($site->famine->stage === 'none' ? 1 : 0),
            5,
            100
        );
    }
}
