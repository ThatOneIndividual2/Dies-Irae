<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\QuarantineLevel;
use InvalidArgumentException;

final class SetQuarantine
{
    public function execute(CatastropheEngine $engine, string $settlementId, string $level): void
    {
        if (!in_array($level, QuarantineLevel::all(), true)) {
            throw new InvalidArgumentException("Unknown quarantine level {$level}");
        }
        $engine->settlement($settlementId)->quarantine = $level;
    }
}
