<?php

namespace App\Actions\Hell;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\CountermeasureOutcome;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\Enums\CountermeasureKind;
use App\Domain\Hell\State\ThreatWorld;

final class CloseBreach
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(ThreatWorld $world, string $territoryId, array $factors, ?string $actorId = null): CountermeasureOutcome
    {
        $attempt = new CountermeasureAttempt(CountermeasureKind::CLOSE_BREACH, $territoryId);
        $attempt->actorCharacterId = $actorId;
        $attempt->factors = $factors;
        $breach = $world->openBreachIn($territoryId);
        $attempt->targetBreachId = $breach?->id;
        $attempt->factors['breach_open'] = $breach !== null;

        return $this->engine->attemptCountermeasure($world, $attempt);
    }
}
