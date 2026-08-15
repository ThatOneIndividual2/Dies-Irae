<?php

namespace App\Domain\Apocalypse;

final class AmbientDrift
{
    /**
     * @param  array<string, float>  $accumulators
     * @param  array<string, float>  $perTick
     * @return array{deltas: array<string, int>, accumulators: array<string, float>}
     */
    public function step(array $accumulators, array $perTick): array
    {
        $deltas = [];
        foreach ($perTick as $meter => $amount) {
            $next = ((float) ($accumulators[$meter] ?? 0)) + (float) $amount;
            $whole = (int) ($next > 0 ? floor($next) : ceil($next));
            if ($whole !== 0) {
                $deltas[$meter] = $whole;
                $next -= $whole;
            }
            $accumulators[$meter] = $next;
        }

        return [
            'deltas' => $deltas,
            'accumulators' => $accumulators,
        ];
    }
}
