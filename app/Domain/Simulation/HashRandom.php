<?php

namespace App\Domain\Simulation;

/**
 * Deterministic hash RNG. Same seed material always yields the same float in [0, 1).
 * Does not require an Eloquent World; callers pass the world seed string.
 */
final class HashRandom
{
    public function float(string $worldSeed, string $type, string $salt = ''): float
    {
        $hash = hash('sha256', implode('|', [$worldSeed, $type, $salt]));
        $int = hexdec(substr($hash, 0, 8));

        return $int / 4294967296.0;
    }

    public function int(string $worldSeed, string $type, int $min, int $max, string $salt = ''): int
    {
        if ($max < $min) {
            throw new \InvalidArgumentException('max must be >= min');
        }

        $span = $max - $min + 1;
        $f = $this->float($worldSeed, $type, $salt);

        return $min + (int) floor($f * $span);
    }
}
