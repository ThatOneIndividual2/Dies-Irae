<?php

namespace App\Domain\Hell;

/**
 * Hash RNG matching Feudalism's SimulationRandom contract, without an Eloquent World.
 * Same seed material always yields the same float in [0, 1).
 */
final class DeterministicRng
{
    public function float(
        string $worldSeed,
        int $worldId,
        string $type,
        ?string $date = null,
        string $salt = ''
    ): float {
        $hash = hash('sha256', $this->seedMaterial($worldSeed, $worldId, $type, $date, $salt));
        $int = hexdec(substr($hash, 0, 8));

        return $int / 4294967296.0;
    }

    public function int(
        string $worldSeed,
        int $worldId,
        string $type,
        int $min,
        int $max,
        ?string $date = null,
        string $salt = ''
    ): int {
        if ($max < $min) {
            throw new \InvalidArgumentException('max must be >= min');
        }

        $span = $max - $min + 1;
        $f = $this->float($worldSeed, $worldId, $type, $date, $salt);

        return $min + (int) floor($f * $span);
    }

    public function seedMaterial(
        string $worldSeed,
        int $worldId,
        string $type,
        ?string $date = null,
        string $salt = ''
    ): string {
        $seed = $worldSeed !== '' ? $worldSeed : hash('sha256', $worldId.'|hell');

        return implode('|', [
            $seed,
            (string) $worldId,
            $type,
            (string) ($date ?? ''),
            $salt,
        ]);
    }
}
