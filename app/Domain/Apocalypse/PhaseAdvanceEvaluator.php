<?php

namespace App\Domain\Apocalypse;

final class PhaseAdvanceEvaluator
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private ConditionEvaluator $conditions
    ) {
    }

    /**
     * Returns the next phase definition if the world may advance. Never returns a lower ordinal.
     *
     * @return array<string, mixed>|null
     */
    public function nextPhaseIfReady(WorldSnapshot $snapshot): ?array
    {
        $current = $this->catalog->phase($snapshot->phaseKey);
        $nextKey = $current['advance_to'] ?? null;
        if (!$nextKey) {
            return null;
        }

        $when = $current['advance_when'] ?? null;
        if (!$this->conditions->matches(is_array($when) ? $when : null, $snapshot)) {
            return null;
        }

        $next = $this->catalog->phase($nextKey);
        if ((int) $next['ordinal'] <= $snapshot->phaseOrdinal) {
            return null;
        }

        return $next;
    }
}
