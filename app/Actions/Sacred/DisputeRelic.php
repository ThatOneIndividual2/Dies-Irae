<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\RelicService;
use App\Domain\Support\Transactional;
use App\Models\Relic;
use App\Models\World;
use Carbon\CarbonInterface;

final class DisputeRelic
{
    public function __construct(private RelicService $relics)
    {
    }

    public function execute(World $world, Relic $relic, string $rivalProvenance, CarbonInterface $date): Relic
    {
        return Transactional::run(fn () => $this->relics->dispute($world, $relic, $rivalProvenance, $date));
    }
}
