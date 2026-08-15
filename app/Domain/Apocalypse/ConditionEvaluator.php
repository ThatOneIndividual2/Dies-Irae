<?php

namespace App\Domain\Apocalypse;

final class ConditionEvaluator
{
    /**
     * @param  array<string, mixed>|null  $when
     */
    public function matches(?array $when, WorldSnapshot $snapshot): bool
    {
        if ($when === null || $when === []) {
            return false;
        }

        if (isset($when['min_phase_ordinal']) && $snapshot->phaseOrdinal < (int) $when['min_phase_ordinal']) {
            return false;
        }

        if (isset($when['min_pressure']) && $snapshot->pressure < (int) $when['min_pressure']) {
            return false;
        }

        if (isset($when['min_days_in_phase']) && $snapshot->daysInPhase < (int) $when['min_days_in_phase']) {
            return false;
        }

        foreach ($when['min_meters'] ?? [] as $key => $minimum) {
            if ($snapshot->meters->get((string) $key) < (int) $minimum) {
                return false;
            }
        }

        foreach ($when['max_meters'] ?? [] as $key => $maximum) {
            if ($snapshot->meters->get((string) $key) > (int) $maximum) {
                return false;
            }
        }

        foreach ($when['min_signal_counts'] ?? [] as $key => $minimum) {
            if ($snapshot->signalCount((string) $key) < (int) $minimum) {
                return false;
            }
        }

        foreach ($when['require_milestones'] ?? [] as $key) {
            if (!$snapshot->hasMilestone((string) $key)) {
                return false;
            }
        }

        if (!empty($when['any_of']) && is_array($when['any_of'])) {
            $matched = false;
            foreach ($when['any_of'] as $group) {
                if (is_array($group) && $this->matches($group, $snapshot)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
