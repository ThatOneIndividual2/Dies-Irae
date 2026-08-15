<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\SaintService;
use App\Domain\Support\Transactional;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;

final class RegisterHistoricalSaint
{
    public function __construct(private SaintService $saints)
    {
    }

    public function execute(
        World $world,
        string $key,
        string $name,
        CarbonInterface $date,
        ?string $feastDay = null,
        array $patronages = [],
        ?int $shrineTerritoryId = null
    ): Saint {
        return Transactional::run(fn () => $this->saints->registerHistorical(
            $world,
            $key,
            $name,
            $date,
            $feastDay,
            $patronages,
            $shrineTerritoryId
        ));
    }
}
