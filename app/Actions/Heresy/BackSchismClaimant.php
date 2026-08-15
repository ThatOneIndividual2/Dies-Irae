<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\PapalClaim;
use App\Models\Schism;
use App\Models\SchismSecularBacker;
use Carbon\CarbonInterface;

final class BackSchismClaimant
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(Schism $schism, Character $ruler, PapalClaim $claim, CarbonInterface $date): SchismSecularBacker
    {
        return $this->engine->rulerBacksClaim($schism, $ruler, $claim, $date);
    }
}
