<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\RelicService;
use App\Domain\Support\Transactional;
use App\Models\Relic;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;

final class RegisterRelic
{
    public function __construct(private RelicService $relics)
    {
    }

    public function execute(
        World $world,
        string $key,
        string $name,
        string $category,
        string $trueNature,
        string $claimedAuthenticity,
        ?string $claimedProvenance,
        CarbonInterface $date,
        ?Saint $saint = null,
        ?int $holdingId = null,
        ?int $territoryId = null,
        int $pilgrimageValue = 10
    ): Relic {
        return Transactional::run(fn () => $this->relics->register(
            $world,
            $key,
            $name,
            $category,
            $trueNature,
            $claimedAuthenticity,
            $claimedProvenance,
            $date,
            $saint,
            $holdingId,
            $territoryId,
            $pilgrimageValue
        ));
    }
}
