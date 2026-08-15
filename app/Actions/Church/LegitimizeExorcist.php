<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\ExorcistLegitimacy;
use Carbon\CarbonInterface;

final class LegitimizeExorcist
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $actor,
        Character $exorcist,
        CarbonInterface $date,
        string $scope = 'general'
    ): ExorcistLegitimacy {
        return $this->church->legitimizeExorcist($actor, $exorcist, $date, $scope);
    }
}
