<?php

namespace App\Domain\Demons;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\CountermeasureOutcome;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\NamedInfernalEngine;
use App\Domain\Hell\PulseResult;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\ThreatWorld;

/**
 * Domain contract for Demons. Bound by DemonsServiceProvider.
 * Implementations must not import or query Feudalism.
 * Hell is not a realm: the engine lives in Domain\Hell and is reached through this contract.
 */
interface DemonsContract
{
    public function domainKey(): string;

    public function engine(): DemonicThreatEngine;

    public function namedInfernal(): NamedInfernalEngine;

    public function pulse(ThreatWorld $world): PulseResult;

    public function attemptCountermeasure(ThreatWorld $world, CountermeasureAttempt $attempt): CountermeasureOutcome;

    public function destroyNamedDemon(ThreatWorld $world, string $demonId, string $method): NamedDemonRecord;

    public function permitNamedDemonReturn(
        ThreatWorld $world,
        string $demonId,
        string $loreReason,
        string $permissionKind
    ): NamedDemonRecord;
}
