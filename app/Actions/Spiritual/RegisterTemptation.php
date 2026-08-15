<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\TemptationService;
use App\Domain\Support\Transactional;
use App\Models\Character;
use App\Models\Temptation;
use App\Models\World;
use Carbon\CarbonInterface;

final class RegisterTemptation
{
    public function __construct(private TemptationService $temptations)
    {
    }

    public function execute(
        World $world,
        Character $character,
        string $vice,
        int $intensity,
        CarbonInterface $date,
        string $stage = 'pressing',
        ?string $sourceType = null,
        ?int $sourceId = null
    ): Temptation {
        return Transactional::run(function () use ($world, $character, $vice, $intensity, $date, $stage, $sourceType, $sourceId) {
            return $this->temptations->open($world, $character, $vice, $intensity, $date, $stage, $sourceType, $sourceId);
        });
    }
}
