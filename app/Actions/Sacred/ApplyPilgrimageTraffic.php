<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\PilgrimageService;
use App\Domain\Support\Transactional;
use App\Models\PilgrimageRoute;
use App\Models\PilgrimageTraffic;
use App\Models\World;
use Carbon\CarbonInterface;

final class ApplyPilgrimageTraffic
{
    public function __construct(private PilgrimageService $pilgrimages)
    {
    }

    public function execute(World $world, PilgrimageRoute $route, int $pilgrims, CarbonInterface $date, ?int $territoryId = null): PilgrimageTraffic
    {
        return Transactional::run(fn () => $this->pilgrimages->applyTraffic($world, $route, $pilgrims, $date, $territoryId));
    }
}
