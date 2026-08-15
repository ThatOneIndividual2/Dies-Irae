<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\PapalClaim;
use App\Models\Schism;
use App\Models\SchismSeeAllegiance;
use App\Models\See;
use Carbon\CarbonInterface;

final class PledgeSchismSee
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(Schism $schism, See $see, PapalClaim $claim, CarbonInterface $date): SchismSeeAllegiance
    {
        return $this->engine->pledgeSee($schism, $see, $claim, $date);
    }
}
