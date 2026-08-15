<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\CultOrganization;
use App\Models\CultRitual;
use Carbon\CarbonInterface;

final class PerformCultRitual
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        CultOrganization $org,
        string $riteKey,
        CarbonInterface $date,
        bool $sacrifice = false,
        int $corruption = 0
    ): CultRitual {
        return $this->engine->performRitual($org, $riteKey, $date, $sacrifice, $corruption);
    }
}
