<?php

namespace App\Domain\Sacred;

use App\Domain\Enums\PilgrimageStatus;
use App\Domain\Sacred\Ports\PlagueTravelPort;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\Pilgrimage;
use App\Models\PilgrimageRoute;
use App\Models\PilgrimageStop;
use App\Models\PilgrimageTraffic;
use App\Models\Relic;
use App\Models\Saint;
use App\Models\Settlement;
use App\Models\World;
use Carbon\CarbonInterface;
use DomainException;

final class PilgrimageService
{
    public function __construct(
        private PlagueTravelPort $plagueTravel,
        private SaintService $saints
    ) {
    }

    public function establishRoute(
        World $world,
        string $key,
        string $name,
        string $destinationType,
        int $destinationId,
        array $territoryStops,
        ?int $originTerritoryId = null,
        int $prestigeYield = 8,
        int $incomeYield = 12
    ): PilgrimageRoute {
        if ($territoryStops === []) {
            throw new DomainException('A pilgrimage route needs at least one stop.');
        }

        $route = PilgrimageRoute::query()->create([
            'world_id' => $world->id,
            'key' => $key,
            'name' => $name,
            'destination_type' => $destinationType,
            'destination_id' => $destinationId,
            'origin_territory_id' => $originTerritoryId ?? $territoryStops[0],
            'traffic_intensity' => 0,
            'is_active' => true,
            'prestige_yield' => $prestigeYield,
            'income_yield' => $incomeYield,
        ]);

        foreach (array_values($territoryStops) as $index => $territoryId) {
            PilgrimageStop::query()->create([
                'world_id' => $world->id,
                'pilgrimage_route_id' => $route->id,
                'territory_id' => $territoryId,
                'sequence' => $index,
                'lodging_pressure' => 0,
            ]);
        }

        return $route->fresh(['stops']);
    }

    public function undertake(World $world, Character $character, PilgrimageRoute $route, CarbonInterface $date): Pilgrimage
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'pilgrim');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $route->world_id, 'pilgrimage route');
        if (!$route->is_active) {
            throw new DomainException('This pilgrimage route is closed.');
        }

        return Pilgrimage::query()->create([
            'world_id' => $world->id,
            'character_id' => $character->id,
            'pilgrimage_route_id' => $route->id,
            'status' => PilgrimageStatus::EN_ROUTE,
            'started_date' => $date->toDateString(),
        ]);
    }

    public function complete(World $world, Pilgrimage $journey, CarbonInterface $date): Pilgrimage
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $journey->world_id, 'complete pilgrimage');
        if ($journey->status !== PilgrimageStatus::EN_ROUTE) {
            throw new DomainException('This pilgrimage is not en route.');
        }

        $journey->status = PilgrimageStatus::COMPLETED;
        $journey->completed_date = $date->toDateString();
        $journey->save();

        $character = $journey->character;
        $character->prestige = (int) $character->prestige + (int) $journey->route->prestige_yield;
        $character->save();

        $this->applyTraffic($world, $journey->route, 1, $date, (int) $journey->route->origin_territory_id);

        return $journey->fresh();
    }

    public function applyTraffic(
        World $world,
        PilgrimageRoute $route,
        int $pilgrims,
        CarbonInterface $date,
        ?int $territoryId = null
    ): PilgrimageTraffic {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $route->world_id, 'pilgrimage traffic');
        $cfg = config('sacred.pilgrimage');
        $income = $pilgrims * (int) $route->income_yield;
        $prestige = $pilgrims * (int) $route->prestige_yield;
        $cult = $pilgrims * (int) $cfg['cult_growth'];

        $route->traffic_intensity = (int) $route->traffic_intensity + $pilgrims;
        $route->save();

        $destinationTerritory = $territoryId ?? $this->destinationTerritory($route);
        if ($destinationTerritory && $route->destination_type === 'saint') {
            $saint = Saint::query()->find($route->destination_id);
            if ($saint) {
                $this->saints->growCult($world, $saint, $destinationTerritory, min(20, $cult));
            }
        }

        $this->openPlagueLinks($world, $route, (int) $cfg['plague_link_intensity']);

        return PilgrimageTraffic::query()->create([
            'world_id' => $world->id,
            'pilgrimage_route_id' => $route->id,
            'territory_id' => $destinationTerritory,
            'pilgrims_count' => $pilgrims,
            'income_delta' => $income,
            'prestige_delta' => $prestige,
            'cult_delta' => $cult,
            'recorded_date' => $date->toDateString(),
            'metadata' => [
                'legitimacy_delta' => $route->destination_type === 'saint' ? intdiv($prestige, 4) : 0,
            ],
        ]);
    }

    public function plagueLinks(): array
    {
        return $this->plagueTravel->openedLinks();
    }

    private function openPlagueLinks(World $world, PilgrimageRoute $route, int $intensity): void
    {
        $stops = $route->stops()->orderBy('sequence')->get();
        $previous = null;
        foreach ($stops as $stop) {
            $key = $this->settlementKey((int) $world->id, (int) $stop->territory_id);
            if ($previous !== null && $key !== null) {
                $this->plagueTravel->openPilgrimageLink((int) $world->id, $previous, $key, $intensity);
            }
            if ($key !== null) {
                $previous = $key;
            }
        }
    }

    private function settlementKey(int $worldId, int $territoryId): ?string
    {
        $settlement = Settlement::query()
            ->where('world_id', $worldId)
            ->where('territory_id', $territoryId)
            ->first();

        if ($settlement) {
            return $settlement->settlement_key;
        }

        return 'territory-'.$territoryId;
    }

    private function destinationTerritory(PilgrimageRoute $route): ?int
    {
        $last = $route->stops()->orderByDesc('sequence')->first();
        if ($last) {
            return (int) $last->territory_id;
        }

        if ($route->destination_type === 'relic') {
            $relic = Relic::query()->find($route->destination_id);

            return $relic?->current_territory_id ? (int) $relic->current_territory_id : null;
        }

        if ($route->destination_type === 'saint') {
            $saint = Saint::query()->find($route->destination_id);

            return $saint?->shrine_territory_id ? (int) $saint->shrine_territory_id : null;
        }

        return null;
    }
}
