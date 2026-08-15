<?php

namespace App\Domain\Population;

use App\Domain\Enums\RuinState;
use App\Domain\Support\IntClamp;

final class RuinStateMachine
{
    public const VIABILITY_FLOOR = 40;
    public const STRAINED_PEAK_BP = 7000;
    public const DEPOPULATED_PEAK_BP = 3500;
    public const ABANDONED_PEAK_BP = 1000;
    public const DISEASE_STRAIN_BP = 1500;
    public const CORRUPTION_ENTER = 40;
    public const DESPAIR_CORRUPTION = 70;
    public const ABANDONED_TO_RUINED_TICKS = 8;

    /**
     * @return array{state: string, reason: string}
     */
    public function next(Settlement $settlement, bool $hellOccupation = false): array
    {
        $current = $settlement->ruinState;
        $souls = $settlement->cohorts->souls();
        $peak = max(1, $settlement->peakSouls);
        $ofPeak = intdiv($souls * 10000, $peak);
        $disease = $settlement->diseaseBurden();
        $foodCrisis = $settlement->foodDeficitBp() >= 2500;

        if ($hellOccupation || $current === RuinState::OVERRUN && $hellOccupation) {
            return [ 'state' => RuinState::OVERRUN, 'reason' => 'hell_occupation' ];
        }

        if ($hellOccupation) {
            return [ 'state' => RuinState::OVERRUN, 'reason' => 'hell_occupation' ];
        }

        if ($this->shouldCorrupt($settlement) && !in_array($current, [RuinState::OVERRUN], true)) {
            if (in_array($current, [
                RuinState::DEPOPULATED,
                RuinState::ABANDONED,
                RuinState::RUINED,
                RuinState::CORRUPTED,
                RuinState::STRAINED,
            ], true) || $settlement->corruption >= 70) {
                return [ 'state' => RuinState::CORRUPTED, 'reason' => 'despair_and_corruption' ];
            }
        }

        if ($current === RuinState::OVERRUN && !$hellOccupation) {
            return [ 'state' => RuinState::RUINED, 'reason' => 'occupation_lifted' ];
        }

        if ($current === RuinState::CORRUPTED) {
            if ($settlement->corruption < 20 && $settlement->despair < 30 && $souls >= self::VIABILITY_FLOOR) {
                return $this->demographicState($settlement, $ofPeak, $disease, $foodCrisis);
            }

            return [ 'state' => RuinState::CORRUPTED, 'reason' => 'still_corrupted' ];
        }

        if ($current === RuinState::RUINED) {
            if ($souls >= self::VIABILITY_FLOOR && $ofPeak >= self::DEPOPULATED_PEAK_BP) {
                return [ 'state' => RuinState::DEPOPULATED, 'reason' => 'resettled' ];
            }

            return [ 'state' => RuinState::RUINED, 'reason' => 'still_ruined' ];
        }

        $demo = $this->demographicState($settlement, $ofPeak, $disease, $foodCrisis);

        if ($current === RuinState::ABANDONED && $demo['state'] === RuinState::ABANDONED) {
            if ($settlement->ticksAbandoned >= self::ABANDONED_TO_RUINED_TICKS) {
                return [ 'state' => RuinState::RUINED, 'reason' => 'decay' ];
            }
        }

        if ($this->isRegression($current, $demo['state']) && in_array($current, [RuinState::ABANDONED, RuinState::RUINED], true)) {
            if ($demo['state'] === RuinState::FUNCTIONING || $demo['state'] === RuinState::STRAINED) {
                return $demo;
            }
        }

        return $demo;
    }

    /**
     * @return array{state: string, reason: string}
     */
    private function demographicState(Settlement $settlement, int $ofPeak, int $disease, bool $foodCrisis): array
    {
        $souls = $settlement->cohorts->souls();

        if ($souls < self::VIABILITY_FLOOR || $ofPeak < self::ABANDONED_PEAK_BP) {
            if ($souls === 0 && $settlement->ticksAbandoned >= self::ABANDONED_TO_RUINED_TICKS) {
                return [ 'state' => RuinState::RUINED, 'reason' => 'empty_decay' ];
            }

            return [ 'state' => RuinState::ABANDONED, 'reason' => 'below_viability' ];
        }

        if ($ofPeak < self::DEPOPULATED_PEAK_BP) {
            return [ 'state' => RuinState::DEPOPULATED, 'reason' => 'population_collapse' ];
        }

        if ($ofPeak < self::STRAINED_PEAK_BP || $disease >= self::DISEASE_STRAIN_BP || $foodCrisis) {
            return [ 'state' => RuinState::STRAINED, 'reason' => $foodCrisis ? 'food_crisis' : ($disease >= self::DISEASE_STRAIN_BP ? 'disease_burden' : 'labor_loss') ];
        }

        return [ 'state' => RuinState::FUNCTIONING, 'reason' => 'stable' ];
    }

    private function shouldCorrupt(Settlement $settlement): bool
    {
        return $settlement->corruption >= self::CORRUPTION_ENTER
            && $settlement->despair >= self::DESPAIR_CORRUPTION;
    }

    private function isRegression(string $from, string $to): bool
    {
        $order = [
            RuinState::FUNCTIONING => 0,
            RuinState::STRAINED => 1,
            RuinState::DEPOPULATED => 2,
            RuinState::ABANDONED => 3,
            RuinState::RUINED => 4,
            RuinState::CORRUPTED => 5,
            RuinState::OVERRUN => 6,
        ];

        return ($order[$to] ?? 99) < ($order[$from] ?? 0);
    }

    public function apply(Settlement $settlement, bool $hellOccupation = false): string
    {
        $previous = $settlement->ruinState;
        $result = $this->next($settlement, $hellOccupation);
        $settlement->ruinState = $result['state'];
        $settlement->ruinReason = $result['reason'];

        if ($settlement->ruinState === RuinState::ABANDONED || $settlement->ruinState === RuinState::RUINED) {
            if ($previous === RuinState::ABANDONED || $previous === RuinState::RUINED) {
                $settlement->ticksAbandoned++;
            } else {
                $settlement->ticksAbandoned = 1;
            }
        } else {
            $settlement->ticksAbandoned = 0;
        }

        $mods = $settlement->ruinModifiers();
        $settlement->despair = $mods->floorDespair($settlement->despair);
        $settlement->morale = IntClamp::between($settlement->morale, 0, 100);
        if ($mods->moraleFloor > 0 && $settlement->morale < $mods->moraleFloor && $settlement->ruinState === RuinState::FUNCTIONING) {
            // functioning has a soft floor only as recovery target, not a clamp up
        }

        return $previous;
    }
}
