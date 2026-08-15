<?php

namespace App\Domain\Hell\Ports;

/**
 * Apocalypse domain owns phase. Hell only reads intensity/phase as gates and multipliers.
 */
interface ApocalypseReading
{
    public function intensity(int $worldId): int;

    public function phaseKey(int $worldId): string;
}
