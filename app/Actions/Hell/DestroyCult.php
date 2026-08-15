<?php

namespace App\Actions\Hell;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\CountermeasureOutcome;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\Enums\CountermeasureKind;
use App\Domain\Hell\State\ThreatWorld;

final class DestroyCult
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(ThreatWorld $world, string $territoryId, string $cultId, array $factors, ?string $actorId = null): CountermeasureOutcome
    {
        $attempt = new CountermeasureAttempt(CountermeasureKind::DESTROY_CULT, $territoryId);
        $attempt->actorCharacterId = $actorId;
        $attempt->targetCultId = $cultId;
        $attempt->factors = $factors;

        return $this->engine->attemptCountermeasure($world, $attempt);
    }
}
