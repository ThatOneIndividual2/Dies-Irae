<?php

namespace App\Domain\Catastrophe;

use App\Domain\Enums\DisplacementCause;
use App\Domain\Enums\PlagueHostType;
use App\Domain\Enums\SpreadVector;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Population\Displacement;
use App\Domain\Population\Ports\CharacterDeathPort;
use App\Domain\Population\Ports\NullCharacterDeathPort;
use App\Domain\Population\Ports\RecordingArmyAttritionPort;
use App\Domain\Population\Ports\RecordingClergyVacancyPort;
use App\Domain\Population\Ports\RecordingNobleExtinctionPort;
use App\Domain\Population\Ports\RecordingSuccessionPressurePort;
use App\Domain\Population\RefugeeFlow;
use App\Domain\Population\RuinStateMachine;
use App\Domain\Population\Settlement;
use App\Domain\Support\IntClamp;
use App\Domain\Support\WorldBoundary;
use InvalidArgumentException;

/**
 * In-world catastrophe clock. Settlements, links, armies, and plague share one tick.
 */
final class CatastropheEngine
{
    public int $worldId;
    public string $seed;
    public int $tick = 0;
    public string $waveId;
    public PlagueProfile $profile;

    /** @var array<string, Settlement> */
    public array $settlements = [];

    /** @var SettlementLink[] */
    public array $links = [];

    /** @var array<string, PlagueFocus> */
    public array $hosts = [];

    /** @var Displacement[] */
    public array $displacements = [];

    /** @var MortalityConsequences[] */
    public array $mortalityLog = [];

    /** @var list<array{from:string,to:string,reason:string,tick:int}> */
    public array $ruinLog = [];

    public ApplyMassMortality $mortality;
    public RefugeeFlow $refugees;
    public RuinStateMachine $ruin;
    public PlagueTick $plagueTick;
    public CharacterDeathPort $characters;
    public RecordingSuccessionPressurePort $succession;
    public RecordingClergyVacancyPort $clergyVacancies;
    public RecordingArmyAttritionPort $armyAttrition;
    public RecordingNobleExtinctionPort $nobleExtinctions;

    private int $flowSeq = 0;

    public function __construct(
        int $worldId,
        string $seed,
        ?PlagueProfile $profile = null,
        ?CharacterDeathPort $characters = null
    ) {
        $this->worldId = $worldId;
        $this->seed = $seed;
        $this->profile = $profile ?? PlagueProfile::blackDeath();
        $this->waveId = $this->profile->key.'-'.$worldId;
        $this->characters = $characters ?? new NullCharacterDeathPort();
        $this->succession = new RecordingSuccessionPressurePort();
        $this->clergyVacancies = new RecordingClergyVacancyPort();
        $this->armyAttrition = new RecordingArmyAttritionPort();
        $this->nobleExtinctions = new RecordingNobleExtinctionPort();
        $this->ruin = new RuinStateMachine();
        $this->mortality = new ApplyMassMortality(
            $this->ruin,
            $this->characters,
            $this->succession,
            $this->clergyVacancies,
            $this->armyAttrition,
            $this->nobleExtinctions
        );
        $this->refugees = new RefugeeFlow();
        $this->plagueTick = new PlagueTick();
    }

    public function addSettlement(Settlement $settlement): Settlement
    {
        WorldBoundary::assertSameWorld($this->worldId, $settlement->worldId, 'add_settlement');
        $this->settlements[$settlement->id] = $settlement;

        return $settlement;
    }

    public function settlement(string $id): Settlement
    {
        if (!isset($this->settlements[$id])) {
            throw new InvalidArgumentException("Unknown settlement {$id}");
        }

        return $this->settlements[$id];
    }

    public function link(string $fromId, string $toId, string $vector, int $intensity, bool $bidirectional = true, ?string $armyId = null): void
    {
        $from = $this->settlement($fromId);
        $to = $this->settlement($toId);
        WorldBoundary::assertSameWorld($from->worldId, $to->worldId, 'settlement_link');
        $this->links[] = new SettlementLink($this->worldId, $fromId, $toId, $vector, $intensity, true, $armyId);
        if ($bidirectional) {
            $this->links[] = new SettlementLink($this->worldId, $toId, $fromId, $vector, $intensity, true, $armyId);
        }
    }

    public function addArmy(string $armyId, int $heads, string $locationId, int $infectious = 0): PlagueFocus
    {
        $here = $this->settlement($locationId);
        $host = PlagueFocus::army($armyId, $this->waveId, $heads, $here->id);
        if ($infectious > 0) {
            $host->addInfectious(min($infectious, $heads), $this->profile->infectiousTicks);
        }
        $this->hosts[$armyId] = $host;

        return $host;
    }

    public function seedPlague(string $settlementId, int $infectious, int $incubating = 0): void
    {
        $s = $this->settlement($settlementId);
        $infectious = min($infectious, $s->souls());
        if ($infectious > 0) {
            $s->infectiousBatches[] = ['remaining' => $this->profile->infectiousTicks, 'souls' => $infectious];
        }
        if ($incubating > 0) {
            $s->incubationBatches[] = ['remaining' => $this->profile->incubationTicks, 'souls' => $incubating];
        }
        $s->syncBurdenFromBatches();
    }

    public function setLocalSupernatural(string $settlementId, int $bp): void
    {
        $this->settlement($settlementId); // exists
        $this->hosts['sup:'.$settlementId] = $this->hosts['sup:'.$settlementId] ?? PlagueFocus::forSettlement($settlementId, $this->waveId, 0, $bp);
        $this->hosts['sup:'.$settlementId]->localSupernaturalBp = $bp;
    }

    public function localSupernatural(string $settlementId): int
    {
        $key = 'sup:'.$settlementId;
        if (!isset($this->hosts[$key])) {
            return 10000;
        }

        return $this->hosts[$key]->localSupernaturalBp;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function tick(int $days = 1): array
    {
        $summaries = [];
        for ($i = 0; $i < $days; $i++) {
            $this->tick++;
            $summaries[] = $this->tickOnce();
        }

        return $summaries;
    }

    /**
     * @return array<string,mixed>
     */
    private function tickOnce(): array
    {
        $newInfections = 0;
        $deaths = 0;
        $spreadEvents = 0;

        foreach ($this->settlements as $settlement) {
            $sup = $this->localSupernatural($settlement->id);
            $progress = $this->advanceSettlementInfection($settlement);
            $newInfections += $this->plagueTick->infectSettlement($settlement, $this->profile, $sup);
            $resolved = $this->plagueTick->resolveCases(
                $settlement,
                $this->profile,
                $progress['resolved'],
                $this->mortality,
                $sup
            );
            $deaths += $resolved['deaths'];
            $this->eatFood($settlement);
            $this->driftMood($settlement);
        }

        foreach ($this->hosts as $host) {
            if ($host->hostType !== PlagueHostType::ARMY) {
                continue;
            }
            $progress = $this->plagueTick->progressHost($host, $this->profile);
            $newInfections += $this->plagueTick->infectHost($host, $this->profile);
            $this->plagueTick->resolveHostCases($host, $this->profile, $progress['resolved']);
            if ($host->locationSettlementId && isset($this->settlements[$host->locationSettlementId])) {
                $this->plagueTick->mixArmyAndSettlement($host, $this->settlements[$host->locationSettlementId], $this->profile);
            }
        }

        foreach ($this->links as $link) {
            if (!$link->active) {
                continue;
            }
            $from = $this->settlements[$link->fromId] ?? null;
            $to = $this->settlements[$link->toId] ?? null;
            if ($from === null || $to === null) {
                continue;
            }
            $exported = $this->plagueTick->exportAlongLink($from, $to, $link, $this->profile);
            if ($exported > 0) {
                $spreadEvents++;
            }
        }

        foreach ($this->settlements as $settlement) {
            $this->autoFlee($settlement);
            $before = $settlement->ruinState;
            $this->ruin->apply($settlement, $settlement->hellOccupation);
            if ($settlement->ruinState !== $before) {
                $this->ruinLog[] = [
                    'from' => $before,
                    'to' => $settlement->ruinState,
                    'reason' => $settlement->ruinReason,
                    'tick' => $this->tick,
                    'settlement_id' => $settlement->id,
                ];
            }
        }

        return [
            'tick' => $this->tick,
            'new_infections' => $newInfections,
            'deaths' => $deaths,
            'spread_events' => $spreadEvents,
        ];
    }

    /**
     * @return array{became_infectious:int,resolved:int}
     */
    private function advanceSettlementInfection(Settlement $settlement): array
    {
        $became = 0;
        $resolved = 0;
        $nextInc = [];
        foreach ($settlement->incubationBatches as $batch) {
            $remaining = $batch['remaining'] - 1;
            if ($remaining <= 0) {
                $became += $batch['souls'];
            } else {
                $nextInc[] = ['remaining' => $remaining, 'souls' => $batch['souls']];
            }
        }
        $settlement->incubationBatches = $nextInc;
        if ($became > 0) {
            $settlement->infectiousBatches[] = ['remaining' => $this->profile->infectiousTicks, 'souls' => $became];
        }

        $nextInf = [];
        foreach ($settlement->infectiousBatches as $batch) {
            $remaining = $batch['remaining'] - 1;
            if ($remaining <= 0) {
                $resolved += $batch['souls'];
            } else {
                $nextInf[] = ['remaining' => $remaining, 'souls' => $batch['souls']];
            }
        }
        $settlement->infectiousBatches = $nextInf;
        $settlement->syncBurdenFromBatches();

        return ['became_infectious' => $became, 'resolved' => $resolved];
    }

    private function eatFood(Settlement $settlement): void
    {
        $produced = $settlement->foodProduction();
        $demand = $settlement->foodDemand();
        $settlement->foodStores += $produced;
        $eaten = min($settlement->foodStores, $demand);
        $settlement->foodStores -= $eaten;
        if ($eaten < $demand) {
            $settlement->morale = IntClamp::between($settlement->morale - 2, 0, 100);
            $settlement->despair = IntClamp::between($settlement->despair + 3, 0, 100);
        } elseif ($settlement->foodStores > $demand * 7) {
            $settlement->morale = IntClamp::between($settlement->morale + 1, 0, 100);
        }
    }

    private function driftMood(Settlement $settlement): void
    {
        $mods = $settlement->ruinModifiers();
        $settlement->corruption = IntClamp::between(
            $settlement->corruption + (int) ceil($mods->corruptionDrift / 1000),
            0,
            100
        );
        if ($settlement->diseaseBurden() === 0 && $settlement->foodDeficitBp() === 0 && $settlement->clergyCare !== 'absent') {
            $settlement->despair = IntClamp::between($settlement->despair - 1, 0, 100);
            $settlement->morale = IntClamp::between($settlement->morale + 1, 0, 100);
        }
    }

    private function autoFlee(Settlement $settlement): void
    {
        $pressure = $settlement->migrationPressure();
        if ($pressure < 55 || $settlement->souls() < 20) {
            return;
        }
        $dest = $this->bestRefuge($settlement);
        if ($dest === null) {
            return;
        }
        $leaving = max(1, intdiv($settlement->souls() * $pressure, 400));
        $leaving = min($leaving, intdiv($settlement->souls(), 8));
        if ($leaving <= 0) {
            return;
        }
        $this->displace($settlement->id, $dest->id, $leaving, DisplacementCause::PLAGUE);
    }

    public function bestRefuge(Settlement $from): ?Settlement
    {
        $best = null;
        $bestScore = -1;
        foreach ($this->links as $link) {
            if ($link->fromId !== $from->id || !$link->active) {
                continue;
            }
            $to = $this->settlements[$link->toId] ?? null;
            if ($to === null || !$to->ruinModifiers()->acceptsRefugees) {
                continue;
            }
            $score = $to->souls() + (100 - $to->despair) * 10 - intdiv($to->diseaseBurden(), 50);
            if ($to->kind === 'monastery') {
                $score += 400;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $to;
            }
        }

        return $best;
    }

    public function displace(string $fromId, ?string $toId, int $souls, string $cause): Displacement
    {
        $from = $this->settlement($fromId);
        $to = $toId !== null ? $this->settlement($toId) : null;
        $this->flowSeq++;
        $flow = $this->refugees->depart($from, $to, $souls, $cause, $this->tick, 'flow-'.$this->worldId.'-'.$this->flowSeq);
        if ($to !== null) {
            $this->refugees->arrive($flow, $to, $this->mortality);
        }
        $this->displacements[] = $flow;
        $this->ruin->apply($from, $from->hellOccupation);
        if ($to !== null) {
            $this->ruin->apply($to, $to->hellOccupation);
        }

        return $flow;
    }

    public function moveArmy(string $armyId, string $toSettlementId): void
    {
        if (!isset($this->hosts[$armyId])) {
            throw new InvalidArgumentException("Unknown army {$armyId}");
        }
        $to = $this->settlement($toSettlementId);
        $army = $this->hosts[$armyId];
        $fromId = $army->locationSettlementId;
        $army->locationSettlementId = $to->id;
        if ($army->infectious > 0 || $army->incubating > 0) {
            $seed = max($army->infectious > 0 ? 1 : 0, intdiv($army->infectious, 20));
            if ($seed > 0 && $to->susceptible() > 0) {
                $to->incubationBatches[] = ['remaining' => $this->profile->incubationTicks, 'souls' => min($seed, $to->susceptible())];
                $to->syncBurdenFromBatches();
            }
        }
        if ($fromId !== null && isset($this->settlements[$fromId])) {
            $this->link($fromId, $to->id, SpreadVector::ARMY, 8000, false, $armyId);
        }
    }

    public function occupyWithHell(string $settlementId, bool $occupied = true): void
    {
        $s = $this->settlement($settlementId);
        $s->hellOccupation = $occupied;
        $before = $s->ruinState;
        $this->ruin->apply($s, $occupied);
        if ($s->ruinState !== $before) {
            $this->ruinLog[] = [
                'from' => $before,
                'to' => $s->ruinState,
                'reason' => $s->ruinReason,
                'tick' => $this->tick,
                'settlement_id' => $s->id,
            ];
        }
    }

    public function totalSouls(): int
    {
        $n = 0;
        foreach ($this->settlements as $s) {
            $n += $s->souls();
        }
        foreach ($this->displacements as $flow) {
            if ($flow->status === 'in_transit' || $flow->status === 'turned_away') {
                $n += $flow->souls();
            }
        }

        return $n;
    }

    public function assertNonNegative(): void
    {
        foreach ($this->settlements as $s) {
            if ($s->souls() < 0 || $s->workforce() < 0 || $s->unburied < 0) {
                throw new \RuntimeException("Negative population at {$s->id}");
            }
            foreach ($s->cohorts->toArray() as $class => $n) {
                if ($n < 0) {
                    throw new \RuntimeException("Negative {$class} at {$s->id}");
                }
            }
        }
    }
}
