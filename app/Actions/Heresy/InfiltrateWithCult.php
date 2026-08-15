<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\CultInfiltration;
use App\Models\CultOrganization;
use Carbon\CarbonInterface;

final class InfiltrateWithCult
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        CultOrganization $org,
        Character $character,
        string $targetType,
        CarbonInterface $date,
        ?int $targetId = null
    ): CultInfiltration {
        return $this->engine->infiltrate($org, $character, $targetType, $date, $targetId);
    }
}
