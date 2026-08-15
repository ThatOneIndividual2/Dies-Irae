<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\Relic;
use Carbon\CarbonInterface;

final class RecognizeRelicAuthenticity
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, Relic $relic, string $authenticity, CarbonInterface $date): Relic
    {
        return $this->church->recognizeRelicAuthenticity($actor, $relic, $authenticity, $date);
    }
}
