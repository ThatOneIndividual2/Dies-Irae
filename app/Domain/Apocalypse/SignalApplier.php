<?php

namespace App\Domain\Apocalypse;

final class SignalApplier
{
    public function __construct(private ApocalypseCatalog $catalog)
    {
    }

    /**
     * @return array<string, int>  integer meter deltas after magnitude, before resistance efficiency
     */
    public function meterDeltas(string $signalKey, int $magnitude): array
    {
        $signal = $this->catalog->signal($signalKey);
        $magnitude = max(1, min(100, $magnitude));
        $deltas = [];

        foreach ($signal['meter_deltas'] ?? [] as $meter => $weight) {
            $deltas[$meter] = (int) round(((float) $weight) * $magnitude);
        }

        return $deltas;
    }

    /**
     * Resistance is weakened by the current phase. Escalation is not.
     *
     * @param  array<string, int>  $deltas
     * @param  array<string, mixed>  $phase
     * @return array<string, int>
     */
    public function applyPhaseEfficiency(array $deltas, array $phase, string $polarity): array
    {
        if ($polarity !== 'resistance') {
            return $deltas;
        }

        $efficiency = (float) ($phase['resistance_efficiency'] ?? 1.0);
        $scaled = [];
        foreach ($deltas as $meter => $delta) {
            $scaled[$meter] = (int) round($delta * $efficiency);
        }

        return $scaled;
    }

    /**
     * @return array<string, int>
     */
    public function localDeltas(string $signalKey, int $magnitude): array
    {
        $signal = $this->catalog->signal($signalKey);
        $magnitude = max(1, min(100, $magnitude));
        $deltas = [];

        foreach ($signal['local_deltas'] ?? [] as $key => $weight) {
            $deltas[$key] = (int) round(((float) $weight) * max(1, $magnitude / 4));
        }

        return $deltas;
    }
}
