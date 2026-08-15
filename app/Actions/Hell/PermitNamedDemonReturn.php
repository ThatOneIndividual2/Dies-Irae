<?php

namespace App\Actions\Hell;

use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\ThreatWorld;

final class PermitNamedDemonReturn
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(
        ThreatWorld $world,
        string $demonId,
        string $loreReason,
        string $permissionKind
    ): NamedDemonRecord {
        return $this->engine->permitNamedDemonReturn($world, $demonId, $loreReason, $permissionKind);
    }
}
