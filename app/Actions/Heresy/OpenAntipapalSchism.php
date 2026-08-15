<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\Papacy;
use App\Models\Schism;
use App\Models\SpiritualOffice;
use Carbon\CarbonInterface;

final class OpenAntipapalSchism
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        Character $claimant,
        Papacy $papacy,
        CarbonInterface $date,
        ?SpiritualOffice $claimantOffice = null,
        string $key = 'antipapal-schism'
    ): Schism {
        return $this->engine->openAntipapalSchism($claimant, $papacy, $date, $claimantOffice, $key);
    }
}
