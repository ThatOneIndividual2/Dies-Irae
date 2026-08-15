<?php

namespace App\Actions\Hell;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\CountermeasureOutcome;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\State\ThreatWorld;

final class AttemptCountermeasure
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(ThreatWorld $world, CountermeasureAttempt $attempt): CountermeasureOutcome
    {
        return $this->engine->attemptCountermeasure($world, $attempt);
    }
}
