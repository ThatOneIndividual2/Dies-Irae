<?php

namespace App\Domain\Apocalypse;

final class MeterBoundPolicy
{
    /**
     * Merge floors by taking the stricter (higher) bound.
     *
     * @param  array<string, int>  $existing
     * @param  array<string, int>  $incoming
     * @return array<string, int>
     */
    public function mergeFloors(array $existing, array $incoming): array
    {
        $merged = $existing;
        foreach ($incoming as $key => $floor) {
            $merged[$key] = max((int) ($merged[$key] ?? 0), (int) $floor);
        }

        return $merged;
    }

    /**
     * Merge ceilings by taking the stricter (lower) bound.
     *
     * @param  array<string, int>  $existing
     * @param  array<string, int>  $incoming
     * @return array<string, int>
     */
    public function mergeCeilings(array $existing, array $incoming): array
    {
        $merged = $existing;
        foreach ($incoming as $key => $ceiling) {
            $current = $merged[$key] ?? 100;
            $merged[$key] = min((int) $current, (int) $ceiling);
        }

        return $merged;
    }
}
