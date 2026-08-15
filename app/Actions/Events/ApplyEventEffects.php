<?php

namespace App\Actions\Events;

use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Contracts\ApocalypseReporter;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventWorldView;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\CorruptionState;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\EventChainLink;
use App\Models\EventHiddenLog;
use App\Models\EventHook;
use App\Models\GameEvent;
use App\Models\Monastery;
use App\Models\PlagueWave;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\World;
use Carbon\Carbon;

final class ApplyEventEffects
{
    public function __construct(
        private AdjustTerritoryState $territoryState,
        private EventConditionEvaluator $conditions,
        private ApocalypseReporter $apocalypse
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $ops
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    public function apply(
        World $world,
        EventWorldView $view,
        array $ops,
        array $context,
        bool $hidden
    ): array {
        $results = [];
        foreach ($ops as $op) {
            if (! is_array($op) || empty($op['op'])) {
                continue;
            }
            $when = $op['when'] ?? [];
            $scopeType = (string) ($context['scope_type'] ?? 'world');
            $scopeId = (int) ($context['scope_id'] ?? $world->id);
            if ($when !== [] && ! $this->conditions->matches($when, $view, $scopeType, $scopeId)) {
                $results[] = ['op' => $op['op'], 'skipped' => 'when'];
                continue;
            }
            $result = $this->run($world, $view, $op, $context);
            $results[] = $result;
            if ($hidden) {
                EventHiddenLog::query()->create([
                    'world_id' => $world->id,
                    'game_event_id' => $context['game_event_id'] ?? null,
                    'definition_key' => $context['definition_key'] ?? null,
                    'choice_key' => $context['choice_key'] ?? null,
                    'op' => $op['op'],
                    'result' => $result,
                    'applied_on' => $world->current_date->toDateString(),
                ]);
            }
            if (isset($context['refresh_view']) && is_callable($context['refresh_view'])) {
                $view = $context['refresh_view']($world);
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function run(World $world, EventWorldView $view, array $op, array $context): array
    {
        $name = (string) $op['op'];
        $territory = $this->territory($world, $view, $context);

        return match ($name) {
            'add_food' => $this->addFood($territory, (int) ($op['delta'] ?? 0)),
            'add_population' => $this->addPopulation($territory, (int) ($op['delta'] ?? 0)),
            'add_despair' => $this->addDespair($territory, (int) ($op['delta'] ?? 0)),
            'add_corruption' => $this->addCorruption($world, $territory, $context, (int) ($op['delta'] ?? 0), (string) ($op['source'] ?? 'event')),
            'add_treasury' => $this->addTreasury($world, $view, $context, (int) ($op['delta'] ?? 0)),
            'add_prestige' => $this->addPrestige($world, $view, $context, (int) ($op['delta'] ?? 0)),
            'add_church_standing' => $this->addChurchStanding($world, $view, $context, (int) ($op['delta'] ?? 0)),
            'apocalypse_signal' => $this->signal($world, $op, $context, $territory),
            'set_hook' => $this->setHook($world, $op, $context, false),
            'add_hook' => $this->setHook($world, $op, $context, true),
            'clear_hook' => $this->clearHook($world, $op, $context),
            'spawn_event' => $this->queueSpawn($world, $op, $context),
            'move_refugees_onward' => $this->moveRefugees($world, $view, $context, $territory, (int) ($op['souls'] ?? 80)),
            'strengthen_cult' => $this->strengthenCult($world, $view, $context, $territory, (int) ($op['delta'] ?? 1)),
            'seed_cult' => $this->seedCult($world, $territory, (int) ($op['strength'] ?? 2)),
            'add_monastery_stores' => $this->addMonasteryStores($world, $view, $context, (int) ($op['delta'] ?? 0)),
            'add_plague_intensity' => $this->addPlague($world, $territory, (int) ($op['delta'] ?? 1)),
            'condemn_ruler' => $this->condemn($world, $view, $context),
            default => ['op' => $name, 'skipped' => 'unknown'],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function territory(World $world, EventWorldView $view, array $context): ?Territory
    {
        $scopeType = (string) ($context['scope_type'] ?? 'world');
        $scopeId = (int) ($context['scope_id'] ?? 0);
        $row = $this->conditions->resolveTerritory($view, $scopeType, $scopeId);
        if ($row === [] || empty($row['id'])) {
            return Territory::query()->where('world_id', $world->id)->orderBy('id')->first();
        }

        return Territory::query()->find($row['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function addFood(?Territory $territory, int $delta): array
    {
        if (! $territory) {
            return ['op' => 'add_food', 'skipped' => 'no_territory'];
        }
        $next = max(0, (int) ($territory->food_stores ?? 0) + $delta);
        $territory->food_stores = $next;
        $territory->save();

        return ['op' => 'add_food', 'territory_id' => $territory->id, 'food_stores' => $next];
    }

    /**
     * @return array<string, mixed>
     */
    private function addPopulation(?Territory $territory, int $delta): array
    {
        if (! $territory) {
            return ['op' => 'add_population', 'skipped' => 'no_territory'];
        }
        $this->territoryState->addPopulation($territory, $delta);

        return ['op' => 'add_population', 'territory_id' => $territory->id, 'delta' => $delta];
    }

    /**
     * @return array<string, mixed>
     */
    private function addDespair(?Territory $territory, int $delta): array
    {
        if (! $territory || $delta === 0) {
            return ['op' => 'add_despair', 'skipped' => $territory ? 'zero' : 'no_territory'];
        }
        $row = $this->territoryState->addDespair($territory, $delta);

        return ['op' => 'add_despair', 'territory_id' => $territory->id, 'intensity' => $row->intensity];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addCorruption(World $world, ?Territory $territory, array $context, int $delta, string $source): array
    {
        $subjectType = (string) ($context['scope_type'] ?? 'territory');
        $subjectId = (int) ($context['scope_id'] ?? 0);
        if ($territory && in_array($subjectType, ['world', 'apocalypse', 'plague', 'war'], true)) {
            $subjectType = 'territory';
            $subjectId = (int) $territory->id;
        }
        if ($subjectId < 1) {
            return ['op' => 'add_corruption', 'skipped' => 'no_subject'];
        }

        $row = CorruptionState::query()
            ->where('world_id', $world->id)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->first();
        if (! $row) {
            $row = CorruptionState::query()->create([
                'world_id' => $world->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'intensity' => max(0, min(100, $delta)),
                'source' => $source,
                'started_date' => $world->current_date->toDateString(),
            ]);
        } else {
            $row->intensity = max(0, min(100, (int) $row->intensity + $delta));
            $row->source = $source;
            $row->save();
        }

        return ['op' => 'add_corruption', 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'intensity' => $row->intensity];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addTreasury(World $world, EventWorldView $view, array $context, int $delta): array
    {
        $id = $this->actorId($view, $context);
        if (! $id) {
            return ['op' => 'add_treasury', 'skipped' => 'no_actor'];
        }
        $character = Character::query()->where('world_id', $world->id)->whereKey($id)->first();
        if (! $character) {
            return ['op' => 'add_treasury', 'skipped' => 'missing'];
        }
        $character->treasury = max(0, (int) ($character->treasury ?? 0) + $delta);
        $character->save();

        return ['op' => 'add_treasury', 'character_id' => $character->id, 'treasury' => $character->treasury];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addPrestige(World $world, EventWorldView $view, array $context, int $delta): array
    {
        $id = $this->actorId($view, $context);
        if (! $id) {
            return ['op' => 'add_prestige', 'skipped' => 'no_actor'];
        }
        $character = Character::query()->whereKey($id)->first();
        if (! $character) {
            return ['op' => 'add_prestige', 'skipped' => 'missing'];
        }
        $character->prestige = (int) ($character->prestige ?? 0) + $delta;
        $character->save();

        return ['op' => 'add_prestige', 'character_id' => $character->id, 'prestige' => $character->prestige];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addChurchStanding(World $world, EventWorldView $view, array $context, int $delta): array
    {
        $id = $this->actorId($view, $context);
        if (! $id) {
            return ['op' => 'add_church_standing', 'skipped' => 'no_actor'];
        }
        $rel = ChurchRelation::query()->where('character_id', $id)->first();
        if (! $rel) {
            $rel = ChurchRelation::query()->create([
                'world_id' => $world->id,
                'character_id' => $id,
                'standing' => $delta,
            ]);
        } else {
            $rel->standing = (int) $rel->standing + $delta;
            $rel->save();
        }

        return ['op' => 'add_church_standing', 'character_id' => $id, 'standing' => $rel->standing];
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function signal(World $world, array $op, array $context, ?Territory $territory): array
    {
        $key = (string) ($op['signal'] ?? '');
        if ($key === '') {
            return ['op' => 'apocalypse_signal', 'skipped' => 'no_signal'];
        }
        $payload = ['source_type' => 'narrative_event', 'source_id' => $context['game_event_id'] ?? null];
        if ($territory) {
            $payload['territory_id'] = $territory->id;
        }
        $row = $this->apocalypse->record($world, $key, (int) ($op['magnitude'] ?? 8), $payload);

        return ['op' => 'apocalypse_signal', 'signal' => $key, 'id' => $row->id];
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function setHook(World $world, array $op, array $context, bool $add): array
    {
        $key = (string) ($op['hook'] ?? '');
        if ($key === '') {
            return ['op' => 'set_hook', 'skipped' => 'no_hook'];
        }
        $scopeType = (string) ($op['scope_type'] ?? $context['scope_type'] ?? 'world');
        $scopeId = array_key_exists('scope_id', $op)
            ? ($op['scope_id'] === null ? null : (int) $op['scope_id'])
            : ($context['scope_id'] ?? null);
        $ttl = isset($op['ttl_days']) ? (int) $op['ttl_days'] : null;
        $expires = $ttl !== null
            ? Carbon::parse($world->current_date)->addDays($ttl)->toDateString()
            : null;
        $intensity = (int) ($op['intensity'] ?? 1);

        $row = EventHook::query()
            ->where('world_id', $world->id)
            ->where('hook_key', $key)
            ->where('scope_type', $scopeType)
            ->where(function ($q) use ($scopeId) {
                if ($scopeId === null) {
                    $q->whereNull('scope_id');
                } else {
                    $q->where('scope_id', $scopeId);
                }
            })
            ->first();

        if (! $row) {
            $row = EventHook::query()->create([
                'world_id' => $world->id,
                'hook_key' => $key,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'intensity' => $intensity,
                'payload' => $op['payload'] ?? ($context['payload'] ?? null),
                'source_event_id' => $context['game_event_id'] ?? null,
                'source_choice' => $context['choice_key'] ?? null,
                'set_on' => $world->current_date->toDateString(),
                'expires_on' => $expires,
            ]);
        } else {
            $row->intensity = $add ? (int) $row->intensity + $intensity : $intensity;
            if ($expires) {
                $row->expires_on = $expires;
            }
            $row->source_event_id = $context['game_event_id'] ?? $row->source_event_id;
            $row->source_choice = $context['choice_key'] ?? $row->source_choice;
            if (isset($op['payload'])) {
                $row->payload = $op['payload'];
            }
            $row->save();
        }

        return ['op' => $add ? 'add_hook' : 'set_hook', 'hook' => $key, 'intensity' => $row->intensity, 'expires_on' => $expires];
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function clearHook(World $world, array $op, array $context): array
    {
        $key = (string) ($op['hook'] ?? '');
        $q = EventHook::query()->where('world_id', $world->id)->where('hook_key', $key);
        if (empty($op['all_scopes'])) {
            $q->where('scope_type', $op['scope_type'] ?? $context['scope_type'] ?? 'world');
            $scopeId = $op['scope_id'] ?? $context['scope_id'] ?? null;
            if ($scopeId === null) {
                $q->whereNull('scope_id');
            } else {
                $q->where('scope_id', $scopeId);
            }
        }
        $n = $q->delete();

        return ['op' => 'clear_hook', 'hook' => $key, 'deleted' => $n];
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function queueSpawn(World $world, array $op, array $context): array
    {
        $days = (int) ($op['days'] ?? 0);
        $due = Carbon::parse($world->current_date)->addDays(max(0, $days))->toDateString();
        $same = ($op['scope'] ?? '') === 'same';
        $link = EventChainLink::query()->create([
            'world_id' => $world->id,
            'chain_key' => $op['chain'] ?? ($context['chain_key'] ?? $context['definition_key'] ?? 'chain'),
            'from_event_id' => $context['game_event_id'] ?? null,
            'from_choice' => $context['choice_key'] ?? null,
            'to_definition_key' => $op['event'] ?? null,
            'scope_type' => $same ? ($context['scope_type'] ?? null) : ($op['scope_type'] ?? $context['scope_type'] ?? null),
            'scope_id' => $same ? ($context['scope_id'] ?? null) : ($op['scope_id'] ?? $context['scope_id'] ?? null),
            'status' => 'pending',
            'due_on' => $due,
            'ops' => $op['ops'] ?? null,
            'when_clause' => $op['when'] ?? null,
            'payload' => $op['payload'] ?? null,
        ]);

        return ['op' => 'spawn_event', 'link_id' => $link->id, 'due_on' => $due, 'event' => $op['event'] ?? null];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function moveRefugees(World $world, EventWorldView $view, array $context, ?Territory $from, int $souls): array
    {
        if (! $from) {
            return ['op' => 'move_refugees_onward', 'skipped' => 'no_from'];
        }
        $neighbors = $view->adjacencies[(int) $from->id] ?? [];
        $best = null;
        $bestFood = -1;
        foreach ($neighbors as $id) {
            $row = $view->territory((int) $id);
            $food = (int) ($row['food_stores'] ?? 0);
            if ($food > $bestFood) {
                $bestFood = $food;
                $best = (int) $id;
            }
        }
        if ($best === null) {
            foreach ($view->territories as $id => $row) {
                if ((int) $id === (int) $from->id) {
                    continue;
                }
                $best = (int) $id;
                break;
            }
        }
        if ($best === null) {
            return ['op' => 'move_refugees_onward', 'skipped' => 'no_destination'];
        }
        $dest = Territory::query()->findOrFail($best);
        $this->territoryState->addPopulation($from, -$souls);
        $this->territoryState->addPopulation($dest, $souls);
        $this->territoryState->addDespair($dest, 4);
        $this->setHook($world, [
            'hook' => 'refugees_in_motion',
            'ttl_days' => 60,
            'intensity' => 1,
            'scope_type' => 'territory',
            'scope_id' => $dest->id,
        ], $context, false);

        return [
            'op' => 'move_refugees_onward',
            'from' => $from->id,
            'to' => $dest->id,
            'souls' => $souls,
            'reason' => $bestFood >= 0 ? 'highest_food_neighbor' : 'first_other',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function strengthenCult(World $world, EventWorldView $view, array $context, ?Territory $territory, int $delta): array
    {
        $cult = null;
        if (($context['scope_type'] ?? '') === 'cult') {
            $cult = Cult::query()->where('world_id', $world->id)->whereKey($context['scope_id'])->first();
        }
        if (! $cult && $territory) {
            $cult = Cult::query()->where('world_id', $world->id)->where('territory_id', $territory->id)->where('destroyed', false)->orderByDesc('strength')->first();
        }
        if (! $cult) {
            return $this->seedCult($world, $territory, max(1, $delta));
        }
        $cult->strength = max(0, min(100, (int) $cult->strength + $delta));
        $cult->activity = max((int) $cult->activity, (int) $cult->strength);
        $cult->save();

        return ['op' => 'strengthen_cult', 'cult_id' => $cult->id, 'strength' => $cult->strength];
    }

    /**
     * @return array<string, mixed>
     */
    private function seedCult(World $world, ?Territory $territory, int $strength): array
    {
        if (! $territory) {
            return ['op' => 'seed_cult', 'skipped' => 'no_territory'];
        }
        $existing = Cult::query()->where('world_id', $world->id)->where('territory_id', $territory->id)->where('destroyed', false)->first();
        if ($existing) {
            $existing->strength = max((int) $existing->strength, $strength);
            $existing->save();

            return ['op' => 'seed_cult', 'cult_id' => $existing->id, 'strength' => $existing->strength, 'existing' => true];
        }
        $faction = DemonicFaction::query()->where('world_id', $world->id)->first();
        if (! $faction) {
            $faction = DemonicFaction::query()->create([
                'world_id' => $world->id,
                'key' => 'whispering_cell',
                'name' => 'A whispering cell',
                'status' => 'active',
            ]);
        }
        $cult = Cult::query()->create([
            'world_id' => $world->id,
            'territory_id' => $territory->id,
            'faction_id' => $faction->id,
            'strength' => $strength,
            'activity' => $strength,
            'revealed' => false,
            'destroyed' => false,
            'key' => 'cell-'.$territory->id,
            'name' => 'Unnamed cell',
        ]);

        return ['op' => 'seed_cult', 'cult_id' => $cult->id, 'strength' => $cult->strength];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addMonasteryStores(World $world, EventWorldView $view, array $context, int $delta): array
    {
        $house = null;
        if (($context['scope_type'] ?? '') === 'monastery') {
            $house = Monastery::query()->whereKey($context['scope_id'])->first();
        }
        if (! $house) {
            $territory = $this->territory($world, $view, $context);
            if ($territory) {
                $house = Monastery::query()->where('world_id', $world->id)->where('territory_id', $territory->id)->first();
            }
        }
        if (! $house) {
            $house = Monastery::query()->where('world_id', $world->id)->first();
        }
        if (! $house) {
            return ['op' => 'add_monastery_stores', 'skipped' => 'no_monastery'];
        }
        $house->stores = max(0, (int) ($house->stores ?? 0) + $delta);
        $house->save();

        return ['op' => 'add_monastery_stores', 'monastery_id' => $house->id, 'stores' => $house->stores];
    }

    /**
     * @return array<string, mixed>
     */
    private function addPlague(World $world, ?Territory $territory, int $delta): array
    {
        if (! $territory) {
            return ['op' => 'add_plague_intensity', 'skipped' => 'no_territory'];
        }
        $wave = PlagueWave::query()->where('world_id', $world->id)->where('status', 'active')->first();
        if (! $wave) {
            return ['op' => 'add_plague_intensity', 'skipped' => 'no_wave'];
        }
        $state = TerritoryPlagueState::query()
            ->where('plague_wave_id', $wave->id)
            ->where('territory_id', $territory->id)
            ->first();
        if (! $state) {
            $state = TerritoryPlagueState::query()->create([
                'world_id' => $world->id,
                'plague_wave_id' => $wave->id,
                'territory_id' => $territory->id,
                'intensity' => max(1, $delta),
                'arrived_date' => $world->current_date->toDateString(),
                'is_active' => true,
            ]);
        } else {
            $state->intensity = max(0, min(10, (int) $state->intensity + $delta));
            $state->save();
        }

        return ['op' => 'add_plague_intensity', 'territory_id' => $territory->id, 'intensity' => $state->intensity];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function condemn(World $world, EventWorldView $view, array $context): array
    {
        $standing = $this->addChurchStanding($world, $view, $context, -8);
        $hook = $this->setHook($world, [
            'hook' => 'bishop_condemned_ruler',
            'ttl_days' => 120,
            'intensity' => 1,
        ], $context, false);

        return ['op' => 'condemn_ruler', 'standing' => $standing, 'hook' => $hook];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function actorId(EventWorldView $view, array $context): ?int
    {
        if (! empty($context['actor_character_id'])) {
            return (int) $context['actor_character_id'];
        }

        return $this->conditions->actorId(
            $view,
            (string) ($context['scope_type'] ?? 'world'),
            (int) ($context['scope_id'] ?? 0)
        );
    }
}
