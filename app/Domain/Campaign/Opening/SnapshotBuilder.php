<?php

namespace App\Domain\Campaign\Opening;

use App\Models\CampaignState;
use App\Models\Cult;
use App\Models\DespairState;
use App\Models\GameEvent;
use App\Models\LocalWar;
use App\Models\SeeTerritory;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\TradeRoute;
use App\Models\VassalRelationship;
use App\Models\World;
use Carbon\Carbon;

final class SnapshotBuilder
{
    public function build(World $world, CampaignState $campaign): WorldConditionSnapshot
    {
        $snap = new WorldConditionSnapshot();
        $start = Carbon::parse($world->start_date);
        $now = Carbon::parse($world->current_date);
        $snap->monthsElapsed = $start->diffInMonths($now);
        $snap->calendarMonth = (int) $now->month;
        $snap->archetype = (string) ($campaign->player_archetype ?? 'count');

        $player = $campaign->player;
        $home = $player?->residence_territory_id;
        if ($home) {
            $homeRow = Territory::query()->find($home);
            $snap->homeTerritory = $homeRow?->key;
        }

        $plague = TerritoryPlagueState::query()
            ->where('world_id', $world->id)
            ->where('is_active', true)
            ->with('territory')
            ->get();
        $snap->plagueTerritories = $plague->map(fn ($s) => $s->territory?->key)->filter()->values()->all();
        $snap->plagueExists = $snap->plagueTerritories !== [];
        if ($home) {
            $local = $plague->firstWhere('territory_id', $home);
            $snap->localPlagueIntensity = (int) ($local->intensity ?? 0);
        }

        $ids = Territory::query()->where('world_id', $world->id)->pluck('id', 'key');
        $plagueIds = $plague->pluck('territory_id')->all();
        if ($home && $plagueIds) {
            $neighborIds = TerritoryAdjacency::query()
                ->where('from_territory_id', $home)
                ->pluck('to_territory_id')
                ->all();
            foreach ($ids as $key => $id) {
                if (in_array((int) $id, $neighborIds, true) && in_array((int) $id, $plagueIds, true)) {
                    $snap->neighborPlague[] = $key;
                }
            }
        }

        $routes = TradeRoute::query()->where('world_id', $world->id)->where('is_active', true)->get();
        $snap->hasTrade = $routes->isNotEmpty();
        $snap->tradeHubs = $routes->flatMap(fn ($r) => [
            optional(Territory::query()->find($r->from_territory_id))->key,
            optional(Territory::query()->find($r->to_territory_id))->key,
        ])->filter()->unique()->values()->all();
        foreach ($routes as $route) {
            $from = Territory::query()->find($route->from_territory_id);
            $to = Territory::query()->find($route->to_territory_id);
            if ($from && in_array($from->key, $snap->plagueTerritories, true) && $to) {
                $snap->tradeToPlague[] = $to->key;
            }
            if ($to && in_array($to->key, $snap->plagueTerritories, true) && $from) {
                $snap->tradeToPlague[] = $from->key;
            }
        }
        $snap->tradeToPlague = array_values(array_unique($snap->tradeToPlague));

        $snap->hasWar = LocalWar::query()->where('world_id', $world->id)->where('status', 'active')->exists();
        $snap->warTheaters = LocalWar::query()->where('world_id', $world->id)->where('status', 'active')
            ->get()
            ->map(fn ($w) => optional(Territory::query()->find($w->theater_territory_id))->key)
            ->filter()
            ->values()
            ->all();

        if ($player) {
            $snap->hasVassal = VassalRelationship::query()
                ->where('liege_character_id', $player->id)
                ->where('is_current', true)
                ->exists();
            $snap->seeTerritories = SeeTerritory::query()
                ->where('world_id', $world->id)
                ->get()
                ->map(fn ($st) => optional(Territory::query()->find($st->territory_id))->key)
                ->filter()
                ->values()
                ->all();
        }

        $snap->despair = (int) DespairState::query()->where('world_id', $world->id)->max('intensity');
        $snap->corruption = (int) \App\Models\CorruptionState::query()->where('world_id', $world->id)->max('intensity');
        $cults = Cult::query()->where('world_id', $world->id)->get();
        $snap->cultActivity = (int) $cults->max('activity');
        $snap->cultRevealed = $cults->contains(fn ($c) => $c->revealed);
        $flags = $campaign->flags ?? [];
        $snap->massDeathFired = (bool) ($flags['mass_death_fired'] ?? false);
        $snap->manifestationFired = (bool) ($flags['manifestation_fired'] ?? false);
        $snap->pendingEvents = GameEvent::query()
            ->where('world_id', $world->id)
            ->where('status', 'awaiting_decision')
            ->count();
        if ($home) {
            $owned = Territory::query()->where('world_id', $world->id)->where('owner_character_id', $player?->id)->pluck('key')->all();
            $snap->realmTerritories = $owned ?: array_filter([$snap->homeTerritory]);
        }

        return $snap;
    }
}
