<?php

namespace App\Domain\Warfare\Engine;

/**
 * Deterministic source for tests and for sealed human-vs-human combat.
 * chance() is always false so possession never fires by accident.
 */
final class NullRandom implements RandomSource
{
    public function int(int $min, int $max): int
    {
        return $min;
    }

    public function chance(int $percent): bool
    {
        return false;
    }
}
