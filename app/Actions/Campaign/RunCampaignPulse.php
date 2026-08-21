<?php

namespace App\Actions\Campaign;

use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Actions\Hell\SetApocalypseStage;
use App\Actions\Time\ScheduleWorldEvent;
use App\Domain\Campaign\Opening\CampaignRng;
use App\Domain\Campaign\Opening\SnapshotBuilder;
use App\Domain\Campaign\Opening\WeightedTriggerEvaluator;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Enums\OverlayState;
use App\Domain\Support\Transactional;
use App\Models\Army;
use App\Models\CampaignEvidence;
use App\Models\CampaignState;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\DemonicInfluence;
use App\Models\GameEvent;
use App\Models\HarvestState;
use App\Models\PlagueWave;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\TradeRoute;
use App\Models\World;
use Carbon\Carbon;

final class RunCampaignPulse
{
    public function __construct(
        private SnapshotBuilder $snapshots,
        private WeightedTriggerEvaluator $evaluator,
        private ScheduleWorldEvent $schedule,
        private AdjustTerritoryState $state,
        private SetApocalypseStage $apocalypse,
        private RecordApocalypseSignal $signals
    ) {
    }

    public function execute(World $world): array
    {
        return Transactional::run(function () use ($world) {
            $campaign = CampaignState::query()->where('world_id', $world->id)->lockForUpdate()->first();
            if (!$campaign) {
                return ['skipped' => true, 'reason' => 'not_a_campaign'];
            }

            $date = Carbon::parse($world->current_date);
            $cfg = config('campaign.1347');
            $rng = new CampaignRng((string) $world->simulation_seed);
            $this->spreadPlague($world, $rng, $date);
            $this->tickHarvest($world, $date);

            $snap = $this->snapshots->build($world->fresh(), $campaign->fresh());
            $fired = [];
            $limit = (int) $cfg['max_events_per_pulse'];
            if ($snap->pendingEvents < (int) $cfg['max_pending_events']) {
                $chosen = $this->evaluator->select(
                    $snap,
                    $rng,
                    $campaign->cooldowns ?? [],
                    $campaign->fired_families ?? [],
                    $snap->archetype,
                    $date->toDateString(),
                    $limit
                );
                foreach ($chosen as $trigger) {
                    $place = $this->placeFor($world, $trigger, $snap, $rng, $date->toDateString());
                    $this->applySilent($world, $trigger['family'], $place, $date);
                    $this->openEvent($world, $campaign, $trigger, $place, $date);
                    $fired[] = $trigger['family'];
                    $this->noteFamily($campaign, $trigger);
                }
            }

            $campaign->pulse_count = (int) $campaign->pulse_count + 1;
            $campaign->last_pulsed_on = $date->toDateString();
            $cooldowns = $campaign->cooldowns ?? [];
            foreach ($cooldowns as $family => $left) {
                $cooldowns[$family] = max(0, (int) $left - 1);
            }
            foreach ($fired as $family) {
                $def = collect(app(\App\Domain\Campaign\Opening\OpeningTriggerCatalog::class)->all())
                    ->firstWhere('family', $family);
                $cooldowns[$family] = (int) ($def['cooldown_pulses'] ?? 2);
            }
            $campaign->cooldowns = $cooldowns;
            $campaign->save();

            $next = $date->copy()->addDays((int) $cfg['pulse_interval_days']);
            $openingUntil = Carbon::parse($campaign->opening_until);
            if ($next->lte($openingUntil)) {
                $this->schedule->execute(
                    $world,
                    'campaign_pulse',
                    $next,
                    ['scenario' => 'europa-1347'],
                    'campaign_pulse:'.$world->id.':'.$next->toDateString()
                );
            }

            return [
                'ok' => true,
                'pulse' => $campaign->pulse_count,
                'fired' => $fired,
            ];
        });
    }

    private function openEvent(World $world, CampaignState $campaign, array $trigger, Territory $place, Carbon $date): GameEvent
    {
        $seq = GameEvent::query()->where('world_id', $world->id)->count() + 1;
        $key = substr($trigger['family'].':'.$place->key.':'.$date->format('Ymd').':'.$seq, 0, 64);
        $options = [];
        foreach ($trigger['options'] as $optKey => $label) {
            $options[$optKey] = $label;
        }

        $event = GameEvent::query()->create([
            'world_id' => $world->id,
            'event_key' => $key,
            'catalog_family' => $trigger['family'],
            'title' => $trigger['title'],
            'body' => $this->localizeBody($trigger['body'], $place),
            'status' => GameEventStatus::AWAITING_DECISION,
            'due_on' => $date->toDateString(),
            'options' => $options,
            'payload' => [
                'catalog_key' => $trigger['key'],
                'family' => $trigger['family'],
                'territory_key' => $place->key,
            ],
            'audience_character_id' => $campaign->player_character_id,
            'territory_id' => $place->id,
        ]);

        CampaignEvidence::query()->create([
            'world_id' => $world->id,
            'observer_character_id' => $campaign->player_character_id,
            'key' => $key,
            'family' => $trigger['family'],
            'interpretation' => $trigger['title'].' at '.$place->name.'.',
            'certainty' => in_array($trigger['family'], ['first_manifestation', 'mass_death'], true) ? 'witnessed' : 'rumor',
            'territory_id' => $place->id,
            'recorded_on' => $date->toDateString(),
        ]);

        return $event;
    }

    private function localizeBody(string $body, Territory $place): string
    {
        return $body.' The report names '.$place->name.'.';
    }

    private function noteFamily(CampaignState $campaign, array $trigger): void
    {
        $fired = $campaign->fired_families ?? [];
        if (!in_array($trigger['family'], $fired, true)) {
            $fired[] = $trigger['family'];
        }
        $campaign->fired_families = $fired;
        $flags = $campaign->flags ?? [];
        if ($trigger['family'] === 'mass_death') {
            $flags['mass_death_fired'] = true;
        }
        if ($trigger['family'] === 'first_manifestation') {
            $flags['manifestation_fired'] = true;
        }
        $campaign->flags = $flags;
    }

    private function placeFor(World $world, array $trigger, $snap, CampaignRng $rng, string $date): Territory
    {
        $policy = $trigger['place_policy'] ?? 'realm';
        $keys = match ($policy) {
            'plague' => $snap->plagueTerritories ?: array_filter([$snap->homeTerritory]),
            'trade_or_plague' => $snap->tradeToPlague ?: $snap->tradeHubs ?: array_filter([$snap->homeTerritory]),
            'trade_hub' => $snap->tradeHubs ?: array_filter([$snap->homeTerritory]),
            'see' => $snap->seeTerritories ?: array_filter([$snap->homeTerritory]),
            'neighbor_of_plague' => $snap->neighborPlague ?: array_filter([$snap->homeTerritory]),
            'war_theater' => $snap->warTheaters ?: array_filter([$snap->homeTerritory]),
            'corruption_or_plague' => $snap->plagueTerritories ?: array_filter([$snap->homeTerritory]),
            default => $snap->realmTerritories ?: array_filter([$snap->homeTerritory]),
        };
        $keys = array_values(array_filter($keys));
        if ($keys === []) {
            return Territory::query()->where('world_id', $world->id)->firstOrFail();
        }
        $pick = $keys[$rng->roll('place', $date, $trigger['key']) % count($keys)];

        return Territory::query()->where('world_id', $world->id)->where('key', $pick)->firstOrFail();
    }

    private function spreadPlague(World $world, CampaignRng $rng, Carbon $date): void
    {
        $wave = PlagueWave::query()->where('world_id', $world->id)->where('status', 'active')->first();
        if (!$wave) {
            return;
        }
        $infected = TerritoryPlagueState::query()
            ->where('world_id', $world->id)
            ->where('is_active', true)
            ->get();
        $already = $infected->pluck('territory_id')->all();
        $candidates = [];
        foreach ($infected as $state) {
            $adj = TerritoryAdjacency::query()->where('from_territory_id', $state->territory_id)->pluck('to_territory_id')->all();
            $trade = TradeRoute::query()->where('world_id', $world->id)->where('is_active', true)
                ->where(function ($q) use ($state) {
                    $q->where('from_territory_id', $state->territory_id)->orWhere('to_territory_id', $state->territory_id);
                })->get();
            foreach ($adj as $id) {
                $candidates[] = (int) $id;
            }
            foreach ($trade as $route) {
                $candidates[] = (int) $route->from_territory_id === (int) $state->territory_id
                    ? (int) $route->to_territory_id
                    : (int) $route->from_territory_id;
            }
        }
        $candidates = array_values(array_unique(array_diff($candidates, $already)));
        foreach ($candidates as $territoryId) {
            $weight = 80 + min(120, $infected->count() * 20);
            if (!$rng->chance($weight, 'spread', $date->toDateString(), (string) $territoryId)) {
                continue;
            }
            TerritoryPlagueState::query()->create([
                'world_id' => $world->id,
                'plague_wave_id' => $wave->id,
                'territory_id' => $territoryId,
                'intensity' => 1,
                'arrived_date' => $date->toDateString(),
                'is_active' => true,
            ]);
            $territory = Territory::query()->find($territoryId);
            if ($territory) {
                $dead = 40 + ($rng->roll('dead', $date->toDateString(), (string) $territoryId) % 80);
                $this->state->addPopulation($territory, -$dead);
                $this->signals->execute($world, 'plague_mortality', min(40, (int) ($dead / 8)), [
                    'territory_id' => $territory->id,
                    'idempotency_key' => 'plague_spread:'.$territory->id.':'.$date->toDateString(),
                ]);
            }
        }
    }

    private function applySilent(World $world, string $family, Territory $place, Carbon $date): void
    {
        if ($family === 'mass_death') {
            $dead = min(400, max(80, (int) floor($place->population * 0.04)));
            $this->state->addPopulation($place, -$dead);
            $this->state->addDespair($place, 6);
            $this->signals->execute($world, 'plague_mortality', 20, [
                'territory_id' => $place->id,
                'idempotency_key' => 'mass_death:'.$place->id.':'.$date->toDateString(),
            ]);
            $this->apocalypse->execute($world, ApocalypseStage::GREAT_MORTALITY, ['mass_death']);
        }
        if ($family === 'first_manifestation') {
            $overlay = SupernaturalOverlay::query()->where('territory_id', $place->id)->where('is_current', true)->first();
            if ($overlay) {
                $overlay->is_current = null;
                $overlay->save();
            }
            SupernaturalOverlay::query()->create([
                'world_id' => $world->id,
                'territory_id' => $place->id,
                'kind' => OverlayState::HAUNTED,
                'changed_on' => $date->toDateString(),
                'is_current' => true,
            ]);
            $faction = DemonicFaction::query()->where('world_id', $world->id)->first();
            if ($faction) {
                Army::query()->create([
                    'world_id' => $world->id,
                    'name' => 'Shape at '.$place->name,
                    'kind' => ArmyKind::DEMONIC,
                    'territory_id' => $place->id,
                    'strength' => 40,
                    'status' => 'idle',
                    'is_active' => true,
                ]);
                Cult::query()->where('world_id', $world->id)->where('territory_id', $place->id)->update(['revealed' => true]);
            }
            $this->state->addDespair($place, 12);
            $this->state->addCorruption('territory', (int) $place->id, (int) $world->id, 12, 'manifestation');
            $this->apocalypse->execute($world, ApocalypseStage::THINNING_VEIL, ['first_manifestation']);
            $this->signals->execute($world, 'desecration', 18, [
                'territory_id' => $place->id,
                'idempotency_key' => 'manifest:'.$place->id.':'.$date->toDateString(),
            ]);
        }
        if ($family === 'disrupted_harvest') {
            $place->food_stores = max(0, (int) $place->food_stores - 10);
            $place->save();
        }
        if ($family === 'refugee_movement') {
            $this->state->addDespair($place, 3);
        }
    }

    private function tickHarvest(World $world, Carbon $date): void
    {
        if ((int) $date->month !== 10 || (int) $date->day > 14) {
            return;
        }
        $year = (int) $date->year;
        foreach (Territory::query()->where('world_id', $world->id)->get() as $territory) {
            if (HarvestState::query()->where('territory_id', $territory->id)->where('harvest_year', $year)->exists()) {
                continue;
            }
            $plague = TerritoryPlagueState::query()->where('territory_id', $territory->id)->where('is_active', true)->first();
            $yield = $plague ? 40 : 70;
            $status = $plague ? 'thin' : 'sound';
            HarvestState::query()->create([
                'world_id' => $world->id,
                'territory_id' => $territory->id,
                'harvest_year' => $year,
                'status' => $status,
                'yield_index' => $yield,
                'recorded_on' => $date->toDateString(),
            ]);
            if ($plague) {
                $territory->food_stores = max(0, (int) $territory->food_stores - 12);
                $territory->save();
            }
        }
    }
}
