<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Catastrophe\PlagueCatalog;
use App\Domain\Enums\SettlementKind;
use App\Domain\Enums\SpreadVector;
use App\Domain\Population\PopulationCohorts;
use App\Domain\Population\Settlement as DomainSettlement;
use App\Models\PlagueWave;
use App\Models\Settlement;
use App\Models\SettlementLink;
use App\Models\SettlementPlagueState;
use App\Models\SettlementPopulation;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\World;
use App\Models\Displacement as DisplacementRow;

/**
 * Persists the settlement-grain plague clock and keeps territory population
 * as a rollup of cohort deaths and flight. Scripted territory edits still
 * go through AdjustTerritoryState, which calls applyDeltaIfPresent.
 */
final class PersistedSettlementPlague
{
    public function __construct(private AdjustTerritoryState $territories)
    {
    }

    public function attachWave(World $world, PlagueWave $wave, Territory $origin, int $intensity): Settlement
    {
        $this->ensureWorld($world);
        $originRow = Settlement::query()
            ->where('world_id', $world->id)
            ->where('settlement_key', $origin->key)
            ->firstOrFail();

        $profile = PlagueCatalog::get($wave->strain ?: 'black_death');
        $wave->origin_settlement_id = $originRow->id;
        $wave->infectiousness = $profile->infectiousness;
        $wave->mortality = $profile->mortality;
        $wave->incubation_ticks = $profile->incubationTicks;
        $wave->infectious_ticks = $profile->infectiousTicks;
        $wave->supernatural_amplification = $profile->supernaturalAmplification;
        $wave->supernatural = $profile->supernatural;
        $wave->save();

        $population = $originRow->population()->firstOrFail();
        $souls = $population->souls();
        $infectious = min($souls, max(8, $intensity * 25));
        $incubating = min(max(0, $souls - $infectious), max(4, $intensity * 15));

        SettlementPlagueState::query()->updateOrCreate(
            ['settlement_id' => $originRow->id, 'wave_id' => $wave->id],
            [
                'world_id' => $world->id,
                'infectious' => $infectious,
                'incubating' => $incubating,
                'recovered' => 0,
                'dead' => 0,
                'local_supernatural_bp' => $this->supernaturalBp($origin),
                'infectious_batches' => [['remaining' => $profile->infectiousTicks, 'souls' => $infectious]],
                'incubation_batches' => $incubating > 0
                    ? [['remaining' => $profile->incubationTicks, 'souls' => $incubating]]
                    : [],
                'arrived_on' => $world->current_date->toDateString(),
            ]
        );

        $population->infectious = $infectious;
        $population->incubating = $incubating;
        $population->as_of_date = $world->current_date->toDateString();
        $population->save();

        return $originRow;
    }

    /**
     * @return array<string, mixed>
     */
    public function tick(World $world): array
    {
        $wave = PlagueWave::query()
            ->where('world_id', $world->id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
        if (!$wave || !SettlementPlagueState::query()->where('wave_id', $wave->id)->exists()) {
            return ['skipped' => true];
        }

        $this->ensureWorld($world);
        $profile = PlagueCatalog::get($wave->strain ?: 'black_death');
        $engine = new CatastropheEngine((int) $world->id, (string) ($world->simulation_seed ?: $world->id), $profile);
        $rows = Settlement::query()->where('world_id', $world->id)->with('population')->get();
        $before = [];

        foreach ($rows as $row) {
            $domain = $this->toDomain($row, $wave);
            $engine->addSettlement($domain);
            $before[$domain->id] = $domain->souls();
            $territory = $row->territory_id ? Territory::query()->find($row->territory_id) : null;
            $engine->setLocalSupernatural($domain->id, $this->supernaturalBp($territory));
        }

        $this->linkEngine($engine, $world);

        $flowCount = count($engine->displacements);
        $engine->tick(1);
        $newFlows = array_slice($engine->displacements, $flowCount);

        $left = [];
        $arrived = [];
        foreach ($newFlows as $flow) {
            $moved = $flow->cohorts->souls();
            $left[$flow->fromId] = ($left[$flow->fromId] ?? 0) + $moved;
            if ($flow->toId) {
                $arrived[$flow->toId] = ($arrived[$flow->toId] ?? 0) + $moved;
            }
            $this->persistFlow($world, $flow);
        }

        $deaths = 0;
        $territoryDelta = [];
        foreach ($engine->settlements as $domain) {
            $row = $rows->firstWhere('settlement_key', $domain->id);
            if (!$row) {
                continue;
            }
            $after = $domain->souls();
            $start = $before[$domain->id] ?? $after;
            $deadNow = max(0, $start - $after - ($left[$domain->id] ?? 0) + ($arrived[$domain->id] ?? 0));
            $deaths += $deadNow;
            $this->writeBack($world, $wave, $row, $domain, $deadNow);
            if ($domain->territoryId) {
                $territoryDelta[$domain->territoryId] = ($territoryDelta[$domain->territoryId] ?? 0) + ($after - $start);
            }
        }

        foreach ($territoryDelta as $territoryId => $delta) {
            if ($delta === 0) {
                continue;
            }
            $territory = Territory::query()->whereKey($territoryId)->first();
            if ($territory) {
                $this->territories->shiftPopulation($territory, $delta);
            }
        }

        return ['skipped' => false, 'deaths' => $deaths, 'tick' => $engine->tick];
    }

    public function applyDeltaIfPresent(Territory $territory, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $settlement = Settlement::query()->where('territory_id', $territory->id)->with('population')->first();
        $population = $settlement?->population;
        if (!$population) {
            return;
        }

        $cohorts = [
            'peasants' => (int) $population->peasants,
            'unfree' => (int) $population->unfree,
            'burghers' => (int) $population->burghers,
            'clergy' => (int) $population->clergy,
            'nobles' => (int) $population->nobles,
        ];

        if ($delta > 0) {
            $cohorts['peasants'] += $delta;
        } else {
            $remaining = -$delta;
            foreach (array_keys($cohorts) as $class) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($cohorts[$class], $remaining);
                $cohorts[$class] -= $take;
                $remaining -= $take;
            }
        }

        $population->fill($cohorts);
        $population->peak_souls = max((int) $population->peak_souls, $population->souls());
        $population->save();
    }

    /**
     * @return array<string, Settlement>
     */
    private function ensureWorld(World $world): array
    {
        $found = [];
        $territories = Territory::query()->where('world_id', $world->id)->get();
        foreach ($territories as $territory) {
            $settlement = Settlement::query()->firstOrCreate(
                ['world_id' => $world->id, 'settlement_key' => $territory->key],
                [
                    'territory_id' => $territory->id,
                    'name' => $territory->name,
                    'kind' => $this->kindFor($territory),
                    'ruin_state' => 'functioning',
                ]
            );
            if (!$settlement->population) {
                $split = $this->split((int) $territory->population);
                $cohorts = new PopulationCohorts(
                    $split['nobles'],
                    $split['clergy'],
                    $split['burghers'],
                    $split['peasants'],
                    $split['unfree']
                );
                SettlementPopulation::query()->create([
                    'world_id' => $world->id,
                    'settlement_id' => $settlement->id,
                    'nobles' => $split['nobles'],
                    'clergy' => $split['clergy'],
                    'burghers' => $split['burghers'],
                    'peasants' => $split['peasants'],
                    'unfree' => $split['unfree'],
                    'peak_souls' => max(1, $cohorts->souls()),
                    'morale' => 60,
                    'despair' => 5,
                    'corruption' => 0,
                    'food_stores' => max((int) $territory->food_stores, $cohorts->foodDemand() * 20),
                    'food_yield_per_worker' => 2,
                    'graveyard_capacity' => max(50, intdiv(max(1, $cohorts->souls()), 4)),
                    'as_of_date' => $world->current_date->toDateString(),
                ]);
            }
            $found[$territory->key] = $settlement->fresh('population');
        }

        $this->ensureLinks($world, $found);

        return $found;
    }

    /**
     * @param  array<string, Settlement>  $byKey
     */
    private function ensureLinks(World $world, array $byKey): void
    {
        $seen = [];
        $edges = TerritoryAdjacency::query()->where('world_id', $world->id)->get();
        $territoryKey = Territory::query()->where('world_id', $world->id)->pluck('key', 'id');

        foreach ($edges as $edge) {
            $fromKey = $territoryKey[$edge->from_territory_id] ?? null;
            $toKey = $territoryKey[$edge->to_territory_id] ?? null;
            if (!$fromKey || !$toKey || !isset($byKey[$fromKey], $byKey[$toKey])) {
                continue;
            }
            $pair = strcmp($fromKey, $toKey) < 0 ? $fromKey.'|'.$toKey : $toKey.'|'.$fromKey;
            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;
            foreach ([[$fromKey, $toKey], [$toKey, $fromKey]] as [$a, $b]) {
                SettlementLink::query()->firstOrCreate(
                    [
                        'world_id' => $world->id,
                        'from_settlement_id' => $byKey[$a]->id,
                        'to_settlement_id' => $byKey[$b]->id,
                        'vector' => SpreadVector::ADJACENT,
                    ],
                    ['intensity' => 5000, 'is_active' => true]
                );
            }
        }
    }

    private function linkEngine(CatastropheEngine $engine, World $world): void
    {
        $seen = [];
        $territoryKey = Territory::query()->where('world_id', $world->id)->pluck('key', 'id');
        foreach (TerritoryAdjacency::query()->where('world_id', $world->id)->get() as $edge) {
            $fromKey = $territoryKey[$edge->from_territory_id] ?? null;
            $toKey = $territoryKey[$edge->to_territory_id] ?? null;
            if (!$fromKey || !$toKey) {
                continue;
            }
            $pair = strcmp($fromKey, $toKey) < 0 ? $fromKey.'|'.$toKey : $toKey.'|'.$fromKey;
            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;
            if (!isset($engine->settlements[$fromKey], $engine->settlements[$toKey])) {
                continue;
            }
            $engine->link($fromKey, $toKey, SpreadVector::ADJACENT, 5000, true);
        }
    }

    private function toDomain(Settlement $row, PlagueWave $wave): DomainSettlement
    {
        $population = $row->population;
        $cohorts = new PopulationCohorts(
            (int) $population->nobles,
            (int) $population->clergy,
            (int) $population->burghers,
            (int) $population->peasants,
            (int) $population->unfree
        );
        $domain = new DomainSettlement(
            (int) $row->world_id,
            (string) $row->settlement_key,
            (string) $row->name,
            (string) $row->kind,
            $cohorts,
            (int) $population->morale,
            (int) $population->despair,
            (int) $population->corruption,
            (int) $population->food_stores,
            (int) $population->food_yield_per_worker,
            (int) $population->graveyard_capacity,
            (string) ($row->ruin_state ?: 'functioning'),
            (string) ($population->quarantine ?: 'none'),
            (string) ($population->corpse_handling ?: 'consecrated'),
            (string) ($population->clergy_care ?: 'parish'),
            $row->territory_id ? (int) $row->territory_id : null,
            $row->holding_id ? (int) $row->holding_id : null
        );
        $domain->peakSouls = max((int) $population->peak_souls, $cohorts->souls(), 1);
        $domain->unburied = (int) $population->unburied;
        $domain->recovered = (int) $population->recovered;
        $domain->ticksAbandoned = (int) $row->ticks_abandoned;
        $domain->hellOccupation = (bool) $row->hell_occupation;

        $state = SettlementPlagueState::query()
            ->where('settlement_id', $row->id)
            ->where('wave_id', $wave->id)
            ->first();
        if ($state) {
            $domain->incubationBatches = $state->incubation_batches ?: [];
            $domain->infectiousBatches = $state->infectious_batches ?: [];
            $domain->recovered = (int) $state->recovered;
            $domain->syncBurdenFromBatches();
        }

        return $domain;
    }

    private function writeBack(World $world, PlagueWave $wave, Settlement $row, DomainSettlement $domain, int $deadNow): void
    {
        $population = $row->population;
        $population->fill([
            'nobles' => $domain->cohorts->nobles,
            'clergy' => $domain->cohorts->clergy,
            'burghers' => $domain->cohorts->burghers,
            'peasants' => $domain->cohorts->peasants,
            'unfree' => $domain->cohorts->unfree,
            'peak_souls' => max((int) $population->peak_souls, $domain->peakSouls, $domain->souls()),
            'morale' => $domain->morale,
            'despair' => $domain->despair,
            'corruption' => $domain->corruption,
            'food_stores' => $domain->foodStores,
            'unburied' => $domain->unburied,
            'incubating' => $domain->incubating,
            'infectious' => $domain->infectious,
            'recovered' => $domain->recovered,
            'quarantine' => $domain->quarantine,
            'corpse_handling' => $domain->corpseHandling,
            'clergy_care' => $domain->clergyCare,
            'as_of_date' => $world->current_date->toDateString(),
        ]);
        $population->save();

        $row->ruin_state = $domain->ruinState;
        $row->ruin_reason = $domain->ruinReason;
        $row->ticks_abandoned = $domain->ticksAbandoned;
        $row->hell_occupation = $domain->hellOccupation;
        $row->save();

        if ($domain->infectious > 0 || $domain->incubating > 0 || $deadNow > 0) {
            $state = SettlementPlagueState::query()->firstOrNew([
                'settlement_id' => $row->id,
                'wave_id' => $wave->id,
            ]);
            $state->world_id = $world->id;
            $state->incubating = $domain->incubating;
            $state->infectious = $domain->infectious;
            $state->recovered = $domain->recovered;
            $state->dead = (int) $state->dead + $deadNow;
            $state->local_supernatural_bp = $this->supernaturalBp($row->territory_id ? Territory::query()->find($row->territory_id) : null);
            $state->incubation_batches = $domain->incubationBatches;
            $state->infectious_batches = $domain->infectiousBatches;
            $state->arrived_on = $state->arrived_on ?: $world->current_date->toDateString();
            $state->save();

            if ($row->territory_id) {
                $this->raiseTerritoryIntensity($world, $wave, (int) $row->territory_id, $domain->diseaseBurden());
            }
        }
    }

    private function raiseTerritoryIntensity(World $world, PlagueWave $wave, int $territoryId, int $burden): void
    {
        $derived = match (true) {
            $burden >= 5000 => 5,
            $burden >= 2500 => 4,
            $burden >= 1000 => 3,
            $burden >= 200 => 2,
            $burden > 0 => 1,
            default => 0,
        };
        if ($derived === 0) {
            return;
        }

        $state = TerritoryPlagueState::query()
            ->where('plague_wave_id', $wave->id)
            ->where('territory_id', $territoryId)
            ->first();
        if (!$state) {
            TerritoryPlagueState::query()->create([
                'world_id' => $world->id,
                'plague_wave_id' => $wave->id,
                'territory_id' => $territoryId,
                'intensity' => $derived,
                'arrived_date' => $world->current_date->toDateString(),
                'is_active' => true,
            ]);

            return;
        }

        if ((int) $state->intensity < $derived) {
            $state->intensity = $derived;
            $state->save();
        }
    }

    private function persistFlow(World $world, $flow): void
    {
        $from = Settlement::query()->where('world_id', $world->id)->where('settlement_key', $flow->fromId)->first();
        $to = $flow->toId
            ? Settlement::query()->where('world_id', $world->id)->where('settlement_key', $flow->toId)->first()
            : null;
        if (!$from) {
            return;
        }

        DisplacementRow::query()->firstOrCreate(
            ['world_id' => $world->id, 'flow_key' => substr($world->current_date->toDateString().':'.$flow->id, 0, 64)],
            [
                'from_settlement_id' => $from->id,
                'to_settlement_id' => $to?->id,
                'cause' => $flow->cause,
                'nobles' => $flow->cohorts->nobles,
                'clergy' => $flow->cohorts->clergy,
                'burghers' => $flow->cohorts->burghers,
                'peasants' => $flow->cohorts->peasants,
                'unfree' => $flow->cohorts->unfree,
                'carrying_infectious' => $flow->carryingInfectious,
                'carrying_incubating' => $flow->carryingIncubating,
                'status' => $flow->status,
                'departed_tick' => $flow->departedTick,
                'arrived_tick' => $flow->arrivedTick,
                'departed_on' => $world->current_date->toDateString(),
                'arrived_on' => $flow->toId ? $world->current_date->toDateString() : null,
            ]
        );
    }

    private function supernaturalBp(?Territory $territory): int
    {
        if (!$territory) {
            return 10000;
        }
        $kind = SupernaturalOverlay::query()
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->value('kind');

        return in_array($kind, ['haunted', 'rifted', 'occupied_by_hell'], true) ? 13000 : 10000;
    }

    /**
     * @return array{nobles:int,clergy:int,burghers:int,peasants:int,unfree:int}
     */
    private function split(int $souls): array
    {
        if ($souls <= 0) {
            return ['nobles' => 0, 'clergy' => 0, 'burghers' => 0, 'peasants' => 0, 'unfree' => 0];
        }

        $nobles = max(1, intdiv($souls, 100));
        $clergy = max(1, intdiv($souls * 2, 100));
        $burghers = intdiv($souls * 15, 100);
        $unfree = intdiv($souls * 27, 100);
        $used = $nobles + $clergy + $burghers + $unfree;
        if ($used > $souls) {
            $unfree = max(0, $souls - $nobles - $clergy - $burghers);
            $used = $nobles + $clergy + $burghers + $unfree;
        }
        $peasants = max(0, $souls - $used);

        return compact('nobles', 'clergy', 'burghers', 'peasants', 'unfree');
    }

    private function kindFor(Territory $territory): string
    {
        return match ($territory->key) {
            'martigues' => SettlementKind::PORT,
            'st_michel' => SettlementKind::MONASTERY,
            'aix', 'salon' => SettlementKind::TOWN,
            default => $territory->terrain_type === 'coast' ? SettlementKind::PORT : SettlementKind::VILLAGE,
        };
    }
}
