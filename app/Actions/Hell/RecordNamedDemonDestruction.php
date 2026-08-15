<?php

namespace App\Actions\Hell;

use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\ThreatWorld;

final class RecordNamedDemonDestruction
{
    public function __construct(private DemonicThreatEngine $engine)
    {
    }

    public function execute(ThreatWorld $world, string $demonId, string $method): NamedDemonRecord
    {
        return $this->engine->destroyNamedDemon($world, $demonId, $method);
    }
}
