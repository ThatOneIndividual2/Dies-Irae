<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\DemonCategory;
use App\Domain\Hell\Enums\HellEventType;
use App\Domain\Hell\Enums\HostKind;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\PossessionStage;
use App\Domain\Hell\State\Breach;
use App\Domain\Hell\State\DemonicHost;
use App\Domain\Hell\State\ThreatEvent;
use App\Domain\Hell\State\ThreatWorld;

/**
 * Hell overlay simulation. Does not own realms, titles, or the apocalypse clock.
 */
final class DemonicThreatEngine
{
    public function __construct(
        private TaxonomyCatalog $catalog,
        private IncursionLifecycle $lifecycle,
        private CountermeasureResolver $countermeasures,
        private NamedDemonPersistence $namedDemons,
        private NamedInfernalEngine $namedInfernal,
        private DeterministicRng $rng
    ) {
    }

    public static function fromDataDirectory(string $directory, ?DeterministicRng $rng = null): self
    {
        $rng = $rng ?? new DeterministicRng();
        $catalog = TaxonomyCatalog::load($directory.'/taxonomy.json');
        $named = new NamedDemonPersistence($catalog);

        return new self(
            $catalog,
            IncursionLifecycle::load($directory.'/incursion_states.json'),
            CountermeasureResolver::load($directory.'/countermeasures.json', $rng),
            $named,
            new NamedInfernalEngine($catalog, $named),
            $rng
        );
    }

    public function catalog(): TaxonomyCatalog
    {
        return $this->catalog;
    }

    public function namedInfernal(): NamedInfernalEngine
    {
        return $this->namedInfernal;
    }

    public function named(): NamedDemonPersistence
    {
        return $this->namedDemons;
    }

    public function pulse(ThreatWorld $world): PulseResult
    {
        $world->assertNotARealm();
        $legalBefore = $world->legalTitles();
        $events = [];
        $transitions = [];

        $events = array_merge($events, $this->tickCults($world));
        $this->namedInfernal->pulseNamed($world);
        $events = array_merge($events, $this->tickNamedPresence($world));
        $events = array_merge($events, $this->tickPossessions($world));
        $events = array_merge($events, $this->tickArmies($world));

        foreach ($world->territoryIdsSorted() as $id) {
            $this->lifecycle->applyEffects($world->territory($id));
        }

        $pressure = new NeighborPressure($this->lifecycle);
        $events = array_merge($events, $pressure->apply($world));
        $events = array_merge($events, $this->evaluateTransitions($world, $transitions));
        $events = array_merge($events, $this->maybeSpawnHosts($world));

        $this->namedDemons->refuseCasualRespawn($world);
        $world->clampAll();
        $this->assertLegalTitlesUntouched($legalBefore, $world);

        $events[] = new ThreatEvent(
            HellEventType::INCURSION_PULSE,
            '*',
            'Hell overlay pulsed.',
            ['intensity' => $world->apocalypseIntensity, 'transitions' => $transitions]
        );

        return new PulseResult($world, $events, $transitions);
    }

    public function attemptCountermeasure(ThreatWorld $world, CountermeasureAttempt $attempt): CountermeasureOutcome
    {
        $this->enrichFactors($world, $attempt);
        $outcome = $this->countermeasures->resolve($world, $attempt, $this->catalog);
        $world->clampAll();
        $this->namedDemons->refuseCasualRespawn($world);

        return $outcome;
    }

    public function openBreach(ThreatWorld $world, string $territoryId, ?string $namedDemonId = null): Breach
    {
        $existing = $world->openBreachIn($territoryId);
        if ($existing) {
            return $existing;
        }

        $id = 'breach-'.$territoryId.'-'.$world->date;
        $breach = new Breach($id, $territoryId, $world->date);
        $breach->namedDemonId = $namedDemonId;
        $world->breaches[$id] = $breach;
        $t = $world->territory($territoryId);
        $t->breachId = $id;
        $t->localManifestation = min(100, $t->localManifestation + 20);
        if (IncursionState::index($t->incursionState) < IncursionState::index(IncursionState::BREACHED)) {
            if ($world->apocalypseIntensity >= 40) {
                $t->incursionState = IncursionState::BREACHED;
            } else {
                $t->incursionState = IncursionState::MANIFESTED;
            }
        }

        return $breach;
    }

    public function destroyNamedDemon(ThreatWorld $world, string $demonId, string $method): \App\Domain\Hell\State\NamedDemonRecord
    {
        return $this->namedDemons->destroy($world, $demonId, $method);
    }

    public function permitNamedDemonReturn(
        ThreatWorld $world,
        string $demonId,
        string $loreReason,
        string $permissionKind
    ): \App\Domain\Hell\State\NamedDemonRecord {
        return $this->namedDemons->permitReturn($world, $demonId, $loreReason, $permissionKind);
    }

    private function tickCults(ThreatWorld $world): array
    {
        $events = [];
        foreach ($world->cults as $cult) {
            if ($cult->destroyed) {
                continue;
            }
            $t = $world->territory($cult->territoryId);
            $drift = (int) floor(($t->despair + $t->corruption) / 40);
            $cult->activity = min(100, $cult->activity + $drift);
            $t->cultActivity = $world->cultActivityIn($t->id);
            if (!$cult->revealed && $cult->activity >= 50) {
                $cult->revealed = true;
                $events[] = new ThreatEvent(
                    HellEventType::CULT_REVEALED,
                    $t->id,
                    "A cult cell is no longer hidden in {$t->id}.",
                    ['cultId' => $cult->id]
                );
            }
        }

        return $events;
    }

    private function tickNamedPresence(ThreatWorld $world): array
    {
        $events = [];
        foreach ($world->namedDemons as $demon) {
            if ($demon->status === NamedDemonStatus::DESTROYED) {
                continue;
            }
            if ($demon->territoryId === null || !NamedDemonStatus::isPresentOnMap($demon->status)) {
                continue;
            }
            $t = $world->territory($demon->territoryId);
            $weight = $this->catalog->rankWeight($demon->catalogKey);
            $t->localManifestation = min(100, $t->localManifestation + (int) ceil($weight / 20));
            $t->corruption = min(100, $t->corruption + (int) ceil($weight / 30));
        }

        return $events;
    }

    private function tickPossessions(ThreatWorld $world): array
    {
        $events = [];
        foreach ($world->possessions as $link) {
            if ($link->stage === PossessionStage::NONE) {
                continue;
            }
            $deepen = false;
            foreach ($world->territories as $t) {
                if ($t->rulerCharacterId === $link->characterId && IncursionState::isAtLeast($t->incursionState, IncursionState::CORRUPTED)) {
                    $deepen = true;
                    $t->rulerTemptation = min(100, $t->rulerTemptation + 6);
                }
            }
            if ($deepen) {
                $before = $link->stage;
                $link->intensity = min(100, $link->intensity + 8);
                if ($link->intensity >= 40 && $link->stage === PossessionStage::TEMPTATION) {
                    $link->stage = PossessionStage::OPPRESSION;
                } elseif ($link->intensity >= 70 && $link->stage === PossessionStage::OPPRESSION) {
                    $link->stage = PossessionStage::POSSESSION;
                }
                if ($before !== $link->stage) {
                    $events[] = new ThreatEvent(
                        HellEventType::POSSESSION_DEEPENED,
                        '*',
                        "Possession of {$link->characterId} deepened to {$link->stage}.",
                        ['characterId' => $link->characterId, 'stage' => $link->stage]
                    );
                }
            }
        }

        return $events;
    }

    private function tickArmies(ThreatWorld $world): array
    {
        foreach ($world->armies as $army) {
            if ($army->cleansed) {
                continue;
            }
            $t = $world->territory($army->territoryId);
            $army->corruption = min(100, $army->corruption + $this->lifecycle->armyCorruption($t->incursionState));
        }

        return [];
    }

    /**
     * @param array<string, string> $transitions
     * @return list<ThreatEvent>
     */
    private function evaluateTransitions(ThreatWorld $world, array &$transitions): array
    {
        $events = [];
        foreach ($world->territoryIdsSorted() as $id) {
            $t = $world->territory($id);
            $next = $this->lifecycle->evaluate($world, $t, $this->catalog);
            if ($next === null || $next === $t->incursionState) {
                continue;
            }
            $from = $t->incursionState;
            $t->incursionState = $next;
            $transitions[$id] = $next;
            if ($next === IncursionState::MANIFESTED) {
                $events[] = new ThreatEvent(HellEventType::MANIFESTATION, $id, "Manifestation in {$id}.", ['from' => $from]);
            }
            if ($next === IncursionState::INFERNAL_STRONGHOLD) {
                $events[] = new ThreatEvent(HellEventType::STRONGHOLD_DECLARED, $id, "Infernal stronghold declared in {$id}.", ['from' => $from]);
            }
            if ($t->settlementCorrupted === false && IncursionState::isAtLeast($next, IncursionState::CORRUPTED)) {
                $t->settlementCorrupted = true;
                $events[] = new ThreatEvent(HellEventType::SETTLEMENT_CORRUPTED, $id, "Settlement {$id} is spiritually corrupted.", []);
            }
        }

        return $events;
    }

    private function maybeSpawnHosts(ThreatWorld $world): array
    {
        $events = [];
        foreach ($world->territoryIdsSorted() as $id) {
            $t = $world->territory($id);
            if (!IncursionState::isAtLeast($t->incursionState, IncursionState::BREACHED)) {
                continue;
            }
            if ($world->livingHostsIn($id) !== []) {
                continue;
            }
            $roll = $this->rng->float($world->seed, $world->worldId, 'hell_host_spawn', $world->date, $id);
            $threshold = IncursionState::isAtLeast($t->incursionState, IncursionState::OVERRUN) ? 0.35 : 0.7;
            if ($roll > $threshold) {
                continue;
            }
            $faction = $t->strongholdFactionKey ?? $this->firstFactionKey($world);
            $host = new DemonicHost('host-'.$id.'-'.$world->date, $id, $faction ?? 'locust_host');
            $host->kind = HostKind::DEMONIC_HOST;
            $host->commanderCatalogKey = 'legion_captain';
            $host->strength = 8 + (int) floor($world->apocalypseIntensity / 10);
            $host->composition = ['shadow_imp' => 6, 'legion_captain' => 1];
            $breach = $world->openBreachIn($id);
            $host->boundBreachId = $breach?->id;
            $world->hosts[$host->id] = $host;
            $events[] = new ThreatEvent(
                HellEventType::HOST_EMERGED,
                $id,
                "A demonic host emerged in {$id}.",
                ['hostId' => $host->id]
            );
        }

        return $events;
    }

    private function firstFactionKey(ThreatWorld $world): ?string
    {
        $keys = array_keys($world->factions);
        sort($keys, SORT_STRING);

        return $keys[0] ?? null;
    }

    private function enrichFactors(ThreatWorld $world, CountermeasureAttempt $attempt): void
    {
        $t = $world->territory($attempt->territoryId);
        $defaults = [
            'local_corruption' => $t->corruption,
            'despair' => $t->despair,
            'apocalypse_intensity' => $world->apocalypseIntensity,
            'cult_activity' => $world->cultActivityIn($t->id),
            'named_demon_rank' => $world->highestNamedRankIn($t->id, fn (string $key) => $this->catalog->rankWeight($key)),
            'clergy_presence' => $t->clergyPresence,
            'population_morale' => $t->morale,
            'plague_burden' => $t->plague,
            'host_strength' => $world->hostStrengthIn($t->id),
            'breach_open' => $world->openBreachIn($t->id) !== null,
            'travel_open' => $t->travelOpen,
            'travel_closed' => !$t->travelOpen,
            'fear' => 100 - $t->morale,
            'incursion_resistance' => IncursionState::index($t->incursionState) * 14,
        ];
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $attempt->factors)) {
                $attempt->factors[$k] = $v;
            }
        }

        $depth = 0;
        if ($attempt->targetCharacterId) {
            foreach ($world->possessions as $link) {
                if ($link->characterId === $attempt->targetCharacterId) {
                    $depth = PossessionStage::index($link->stage) * 25;
                }
            }
        }
        $attempt->factors['possession_depth'] = $attempt->factors['possession_depth'] ?? $depth;
    }

    private function assertLegalTitlesUntouched(array $before, ThreatWorld $world): void
    {
        foreach ($before as $id => $titleId) {
            if ($world->territory($id)->legalTitleId !== $titleId) {
                throw new \LogicException("Hell overlay must not mutate legal title on {$id}");
            }
        }
    }
}
