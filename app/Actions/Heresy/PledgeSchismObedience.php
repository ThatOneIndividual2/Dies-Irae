<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\PapalClaim;
use App\Models\Schism;
use App\Models\SchismObedience;
use Carbon\CarbonInterface;

final class PledgeSchismObedience
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(Schism $schism, Character $character, PapalClaim $claim, CarbonInterface $date): SchismObedience
    {
        return $this->engine->pledgeCharacter($schism, $character, $claim, $date);
    }
}
