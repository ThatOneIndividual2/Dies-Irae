<?php

namespace App\Domain\Demons;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\CountermeasureOutcome;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\NamedInfernalEngine;
use App\Domain\Hell\PulseResult;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\ThreatWorld;

final class DemonsService implements DemonsContract
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function domainKey(): string
    {
        return 'demons';
    }

    public function engine(): DemonicThreatEngine
    {
        return $this->engine;
    }

    public function namedInfernal(): NamedInfernalEngine
    {
        return $this->engine->namedInfernal();
    }

    public function pulse(ThreatWorld $world): PulseResult
    {
        return $this->engine->pulse($world);
    }

    public function attemptCountermeasure(ThreatWorld $world, CountermeasureAttempt $attempt): CountermeasureOutcome
    {
        return $this->engine->attemptCountermeasure($world, $attempt);
    }

    public function destroyNamedDemon(ThreatWorld $world, string $demonId, string $method): NamedDemonRecord
    {
        return $this->engine->destroyNamedDemon($world, $demonId, $method);
    }

    public function permitNamedDemonReturn(
        ThreatWorld $world,
        string $demonId,
        string $loreReason,
        string $permissionKind
    ): NamedDemonRecord {
        return $this->engine->permitNamedDemonReturn($world, $demonId, $loreReason, $permissionKind);
    }
}
