<?php

namespace App\Domain\Economy;

use App\Domain\Enums\GrainMoveMode;
use App\Domain\Support\IntClamp;
use App\Domain\Support\WorldBoundary;
use InvalidArgumentException;

final class GrainLogistics
{
    /**
     * @param  array<string, EconomicSite>  $sites
     * @param  TradeLane[]  $lanes
     */
    public function move(
        array $sites,
        array $lanes,
        string $fromId,
        string $toId,
        int $amount,
        string $mode,
        string $actor,
        int $seq
    ): GrainShipment {
        if (!isset($sites[$fromId], $sites[$toId])) {
            throw new InvalidArgumentException('Unknown settlement for grain movement');
        }
        if (!in_array($mode, GrainMoveMode::all(), true)) {
            throw new InvalidArgumentException("Unknown grain mode {$mode}");
        }

        $from = $sites[$fromId];
        $to = $sites[$toId];
        WorldBoundary::assertSameWorld($from->settlement->worldId, $to->settlement->worldId, 'grain_move');

        $amount = max(0, $amount);
        $lane = $this->lane($lanes, $fromId, $toId);
        $cap = min($from->transportCapacity, $to->transportCapacity, $lane?->usableCapacity() ?? 0);
        if ($cap <= 0) {
            return new GrainShipment('move-'.$seq, $fromId, $toId, 0, $mode, $actor, 0, 'blocked');
        }

        $amount = min($amount, $cap);
        $taken = $this->withdraw($from, $amount, $mode);
        if ($taken <= 0) {
            return new GrainShipment('move-'.$seq, $fromId, $toId, 0, $mode, $actor, 0, 'empty');
        }

        $gold = 0;
        if ($mode === GrainMoveMode::PURCHASE) {
            $gold = intdiv($taken * $from->regionalPrice, 100);
            $gold = min($gold, $to->gold);
            $to->gold -= $gold;
            $from->gold += $gold;
        }

        $this->deposit($to, $taken, $mode);
        $this->socialEffects($from, $to, $taken, $mode);

        return new GrainShipment('move-'.$seq, $fromId, $toId, $taken, $mode, $actor, $gold, 'moved');
    }

    private function withdraw(EconomicSite $from, int $amount, string $mode): int
    {
        if (GrainMoveMode::isChurch($mode) && $from->isMonastery()) {
            $fromMonastery = $from->takeMonasteryStores($amount);
            if ($fromMonastery < $amount) {
                $fromMonastery += $from->takeCivicStores($amount - $fromMonastery);
            }

            return $fromMonastery;
        }

        if ($mode === GrainMoveMode::SEIZURE && $from->isMonastery()) {
            $taken = $from->takeMonasteryStores($amount);
            if ($taken < $amount) {
                $taken += $from->takeCivicStores($amount - $taken);
            }

            return $taken;
        }

        return $from->takeCivicStores($amount);
    }

    private function deposit(EconomicSite $to, int $amount, string $mode): void
    {
        if ($to->isMonastery() && in_array($mode, [GrainMoveMode::DONATION, GrainMoveMode::ALMS, GrainMoveMode::RELIEF], true)) {
            $to->monasteryStores += $amount;

            return;
        }

        $to->addCivicStores($amount);
    }

    private function socialEffects(EconomicSite $from, EconomicSite $to, int $amount, string $mode): void
    {
        if (GrainMoveMode::isChurch($mode)) {
            $from->churchLegitimacy = IntClamp::between($from->churchLegitimacy + 4, 0, 100);
            $to->churchLegitimacy = IntClamp::between($to->churchLegitimacy + 2, 0, 100);
            $to->settlement->morale = IntClamp::between($to->settlement->morale + 3, 0, 100);
            $to->settlement->despair = IntClamp::between($to->settlement->despair - 4, 0, 100);
            $ownDays = $from->dailyDemand() > 0 ? intdiv($from->totalFood(), $from->dailyDemand()) : 99;
            $from->monasteryOverwhelmed = $from->isMonastery() && $ownDays <= 5;
        }

        if (GrainMoveMode::isCoercive($mode)) {
            $from->settlement->despair = IntClamp::between($from->settlement->despair + 6, 0, 100);
            $from->famine->unrest = IntClamp::between($from->famine->unrest + 8, 0, 100);
            if ($from->isMonastery()) {
                $from->churchLegitimacy = IntClamp::between($from->churchLegitimacy - 10, 0, 100);
            }
        }

        unset($amount);
    }

    /**
     * @param  TradeLane[]  $lanes
     */
    private function lane(array $lanes, string $fromId, string $toId): ?TradeLane
    {
        foreach ($lanes as $lane) {
            if ($lane->connects($fromId, $toId)) {
                return $lane;
            }
        }

        return null;
    }
}
