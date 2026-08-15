<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\PilgrimageService;
use App\Domain\Support\Transactional;
use App\Models\PilgrimageRoute;
use App\Models\World;

final class EstablishPilgrimageRoute
{
    public function __construct(private PilgrimageService $pilgrimages)
    {
    }

    public function execute(
        World $world,
        string $key,
        string $name,
        string $destinationType,
        int $destinationId,
        array $territoryStops,
        ?int $originTerritoryId = null
    ): PilgrimageRoute {
        return Transactional::run(fn () => $this->pilgrimages->establishRoute(
            $world,
            $key,
            $name,
            $destinationType,
            $destinationId,
            $territoryStops,
            $originTerritoryId
        ));
    }
}
