<?php

namespace App\Actions\Demons;

use App\Actions\Hell\PulseHellIncursion as HellPulse;
use App\Domain\Hell\PulseResult;
use App\Domain\Hell\State\ThreatWorld;

final class PulseHellIncursion
{
    public function __construct(private HellPulse $inner)
    {
    }

    public function execute(ThreatWorld $world): PulseResult
    {
        return $this->inner->execute($world);
    }
}
