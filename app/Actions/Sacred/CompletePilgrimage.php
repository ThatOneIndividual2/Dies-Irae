<?php

namespace App\Actions\Sacred;

use App\Actions\Spiritual\RecordSpiritualAct;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Sacred\PilgrimageService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\PilgrimageCompleted;
use App\Models\Character;
use App\Models\Pilgrimage;
use App\Models\PilgrimageRoute;
use App\Models\World;
use Carbon\CarbonInterface;

final class CompletePilgrimage
{
    public function __construct(
        private PilgrimageService $pilgrimages,
        private RecordSpiritualAct $acts
    ) {
    }

    public function execute(World $world, Character $character, PilgrimageRoute $route, CarbonInterface $start, CarbonInterface $end): Pilgrimage
    {
        return Transactional::run(function () use ($world, $character, $route, $start, $end) {
            $journey = $this->pilgrimages->undertake($world, $character, $route, $start);
            $completed = $this->pilgrimages->complete($world, $journey, $end);
            $this->acts->execute($world, $character, SpiritualActType::PILGRIMAGE, $end, false);
            AfterCommit::dispatch(fn () => event(new PilgrimageCompleted($completed->id, (int) $character->id, (int) $route->id)));

            return $completed;
        });
    }
}
