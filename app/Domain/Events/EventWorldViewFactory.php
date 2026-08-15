<?php

namespace App\Domain\Events;

use App\Models\ApocalypseState;
use App\Models\Army;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\CorruptionState;
use App\Models\Cult;
use App\Models\DespairState;
use App\Models\Dynasty;
use App\Models\EventCooldown;
use App\Models\EventHook;
use App\Models\GameEvent;
use App\Models\Monastery;
use App\Models\PlagueWave;
use App\Models\Realm;
use App\Models\See;
use App\Models\Settlement;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\Schema;

final class EventWorldViewFactory
{
    public function make(World $world): EventWorldView
    {
        $date = $world->current_date->toDateString();
        $state = ApocalypseState::query()->where('world_id', $world->id)->first();
        $meters = [];
        foreach (['global_corruption', 'plague_severity', 'demonic_manifestation', 'institutional_collapse', 'famine_pressure', 'despair', 'church_cohesion', 'political_fragmentation'] as $meter) {
            $meters[$meter] = (int) ($state->{$meter} ?? 0);
        }

        $hooks = [];
        foreach (EventHook::query()->where('world_id', $world->id)->get() as $hook) {
            if (! $hook->isActiveOn($date)) {
                continue;
            }
            $hooks[] = [
                'hook_key' => $hook->hook_key,
                'scope_type' => $hook->scope_type,
                'scope_id' => $hook->scope_id,
                'intensity' => $hook->intensity,
                'payload' => $hook->payload ?? [],
            ];
        }

        $despair = DespairState::query()->where('world_id', $world->id)->get()->keyBy('territory_id');
        $corruption = CorruptionState::query()
            ->where('world_id', $world->id)
            ->where('subject_type', 'territory')
            ->get()
            ->keyBy('subject_id');
        $plagueByTerritory = TerritoryPlagueState::query()
            ->where('world_id', $world->id)
            ->get()
            ->groupBy('territory_id');

        $territories = [];
        foreach (Territory::query()->where('world_id', $world->id)->get() as $row) {
            $plagueRows = $plagueByTerritory->get($row->id) ?? collect();
            $intensity = 0;
            foreach ($plagueRows as $plagueRow) {
                if (($plagueRow->is_active ?? true) || $plagueRow->peaked_date === null) {
                    $intensity = max($intensity, (int) $plagueRow->intensity);
                }
            }
            $territories[(int) $row->id] = [
                'id' => (int) $row->id,
                'name' => $row->name,
                'key' => $row->key,
                'food_stores' => (int) ($row->food_stores ?? 0),
                'population' => (int) ($row->population ?? 0),
                'levy_available' => (int) ($row->levy_available ?? 0),
                'despair' => (int) (optional($despair->get($row->id))->intensity ?? 0),
                'corruption' => (int) (optional($corruption->get($row->id))->intensity ?? 0),
                'plague_intensity' => $intensity,
                'owner_character_id' => $row->owner_character_id ? (int) $row->owner_character_id : null,
                'ruin_state' => $row->ruin_state ?? 'intact',
            ];
        }

        $standings = ChurchRelation::query()->where('world_id', $world->id)->get()->groupBy('character_id');
        $characters = [];
        foreach (Character::query()->where('world_id', $world->id)->get() as $row) {
            $rel = optional($standings->get($row->id))->first();
            $characters[(int) $row->id] = [
                'id' => (int) $row->id,
                'is_alive' => (bool) $row->is_alive,
                'treasury' => (int) ($row->treasury ?? 0),
                'prestige' => (int) ($row->prestige ?? 0),
                'residence_territory_id' => $row->residence_territory_id ? (int) $row->residence_territory_id : null,
                'dynasty_id' => $row->dynasty_id ? (int) $row->dynasty_id : null,
                'church_standing' => (int) ($rel->standing ?? 0),
            ];
        }

        $dynasties = [];
        foreach (Dynasty::query()->where('world_id', $world->id)->get() as $row) {
            $head = Character::query()
                ->where('world_id', $world->id)
                ->where('dynasty_id', $row->id)
                ->where('is_alive', true)
                ->orderBy('id')
                ->first();
            $dynasties[(int) $row->id] = [
                'id' => (int) $row->id,
                'head_character_id' => $head?->id,
                'prestige' => (int) ($row->prestige ?? 0),
            ];
        }

        $settlements = [];
        if (Schema::hasTable('settlements')) {
            foreach (Settlement::query()->where('world_id', $world->id)->get() as $row) {
                $settlements[(int) $row->id] = [
                    'id' => (int) $row->id,
                    'territory_id' => $row->territory_id ? (int) $row->territory_id : null,
                    'name' => $row->name,
                ];
            }
        }

        $realms = [];
        foreach (Realm::query()->where('world_id', $world->id)->get() as $row) {
            $realms[(int) $row->id] = [
                'id' => (int) $row->id,
                'top_liege_character_id' => $row->top_liege_character_id ? (int) $row->top_liege_character_id : null,
            ];
        }

        $sees = [];
        foreach (See::query()->where('world_id', $world->id)->get() as $row) {
            $sees[(int) $row->id] = [
                'id' => (int) $row->id,
                'territory_id' => $row->territory_id ? (int) $row->territory_id : null,
            ];
        }

        $monasteries = [];
        foreach (Monastery::query()->where('world_id', $world->id)->get() as $row) {
            $monasteries[(int) $row->id] = [
                'id' => (int) $row->id,
                'territory_id' => $row->territory_id ? (int) $row->territory_id : null,
                'stores' => (int) ($row->stores ?? 0),
            ];
        }

        $armies = [];
        foreach (Army::query()->where('world_id', $world->id)->get() as $row) {
            $armies[(int) $row->id] = [
                'id' => (int) $row->id,
                'territory_id' => $row->territory_id ? (int) $row->territory_id : null,
                'strength' => (int) ($row->strength ?? 0),
                'is_active' => (bool) $row->is_active,
                'owner_character_id' => $row->owner_character_id ? (int) $row->owner_character_id : null,
                'commander_character_id' => $row->commander_character_id ? (int) $row->commander_character_id : null,
            ];
        }

        $wars = [];
        if (Schema::hasTable('warfare_wars')) {
            foreach (\Illuminate\Support\Facades\DB::table('warfare_wars')->where('world_id', $world->id)->get() as $row) {
                $wars[(int) $row->id] = [
                    'id' => (int) $row->id,
                    'status' => $row->status,
                    'kind' => $row->kind,
                ];
            }
        }

        $cults = [];
        foreach (Cult::query()->where('world_id', $world->id)->get() as $row) {
            $cults[(int) $row->id] = [
                'id' => (int) $row->id,
                'territory_id' => $row->territory_id ? (int) $row->territory_id : null,
                'strength' => (int) ($row->strength ?? 0),
                'destroyed' => (bool) $row->destroyed,
            ];
        }

        $plagues = [];
        foreach (PlagueWave::query()->where('world_id', $world->id)->get() as $row) {
            $originTerritory = null;
            if ($row->origin_settlement_id && isset($settlements[(int) $row->origin_settlement_id])) {
                $originTerritory = $settlements[(int) $row->origin_settlement_id]['territory_id'] ?? null;
            }
            $firstState = $row->territoryStates()->first();
            $plagues[(int) $row->id] = [
                'id' => (int) $row->id,
                'status' => $row->status,
                'origin_territory_id' => $originTerritory ?: ($firstState?->territory_id),
            ];
        }

        $adjacencies = [];
        if (Schema::hasTable('territory_adjacencies')) {
            foreach (TerritoryAdjacency::query()->get() as $row) {
                if (! isset($territories[(int) $row->from_territory_id])) {
                    continue;
                }
                $adjacencies[(int) $row->from_territory_id][] = (int) $row->to_territory_id;
            }
        }

        $cooldowns = [];
        foreach (EventCooldown::query()->where('world_id', $world->id)->get() as $row) {
            $cooldowns[$row->definition_key.'|'.$row->scope_type.'|'.(int) $row->scope_id] = $row->available_on->toDateString();
        }

        $pending = [];
        foreach (GameEvent::query()->where('world_id', $world->id)->get() as $row) {
            $pending[] = [
                'id' => (int) $row->id,
                'definition_key' => $row->definition_key ?: $row->event_key,
                'status' => $row->status,
                'scope_type' => $row->scope_type,
                'scope_id' => $row->scope_id,
                'exclusivity_group' => $row->exclusivity_group,
                'engine' => $row->engine ?? 'campaign_beat',
            ];
        }

        $playerIds = [];
        foreach (User::query()->where('world_id', $world->id)->whereNotNull('controlled_character_id')->get() as $user) {
            $playerIds[(int) $user->controlled_character_id] = true;
        }

        return new EventWorldView(
            (int) $world->id,
            $date,
            (string) ($world->simulation_seed ?? 'seed'),
            (string) ($state->phase_key ?? 'ordinary'),
            (int) ($state->phase_ordinal ?? 1),
            (int) ($state->pressure ?? 0),
            $meters,
            $hooks,
            $territories,
            $characters,
            $dynasties,
            $settlements,
            $realms,
            $sees,
            $monasteries,
            $armies,
            $wars,
            $cults,
            $plagues,
            $adjacencies,
            $cooldowns,
            $pending,
            $playerIds
        );
    }
}
