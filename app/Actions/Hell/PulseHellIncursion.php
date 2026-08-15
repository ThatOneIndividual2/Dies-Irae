<?php

namespace App\Actions\Hell;

use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\PulseResult;
use App\Domain\Hell\State\ThreatWorld;

final class PulseHellIncursion
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(ThreatWorld $world): PulseResult
    {
        return $this->engine->pulse($world);
    }
}
