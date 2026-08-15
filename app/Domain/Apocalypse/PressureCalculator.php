<?php

namespace App\Domain\Apocalypse;

final class PressureCalculator
{
    /**
     * @param  array<string, float>  $weights
     */
    public function __construct(private array $weights)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('apocalypse.pressure_weights', []));
    }

    public function compute(ApocalypseMeters $meters): int
    {
        $sum =
            ($this->weights['global_corruption'] ?? 0.18) * $meters->get('global_corruption')
            + ($this->weights['plague_severity'] ?? 0.16) * $meters->get('plague_severity')
            + ($this->weights['demonic_manifestation'] ?? 0.16) * $meters->get('demonic_manifestation')
            + ($this->weights['institutional_collapse'] ?? 0.12) * $meters->get('institutional_collapse')
            + ($this->weights['famine_pressure'] ?? 0.10) * $meters->get('famine_pressure')
            + ($this->weights['despair'] ?? 0.12) * $meters->get('despair')
            + ($this->weights['political_fragmentation'] ?? 0.10) * $meters->get('political_fragmentation')
            + ($this->weights['church_cohesion_deficit'] ?? 0.06) * (100 - $meters->get('church_cohesion'));

        return ApocalypseMeters::clamp((int) round($sum));
    }
}
