<?php

namespace App\Domain\Population;

use App\Domain\Enums\DisplacementCause;
use App\Domain\Enums\RuinState;
use App\Domain\Enums\SocialClass;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\WorldBoundary;
use InvalidArgumentException;

final class RefugeeFlow
{
    public const ROAD_LOSS_BP = 400;

    /** @var array<string,int> */
    public const FLIGHT_WEIGHTS = [
        SocialClass::NOBLES => 25,
        SocialClass::CLERGY => 40,
        SocialClass::BURGHERS => 90,
        SocialClass::PEASANTS => 140,
        SocialClass::UNFREE => 110,
    ];

    /** @var array<string,int> */
    public const COLLAPSE_FLIGHT_WEIGHTS = [
        SocialClass::NOBLES => 90,
        SocialClass::CLERGY => 70,
        SocialClass::BURGHERS => 110,
        SocialClass::PEASANTS => 130,
        SocialClass::UNFREE => 120,
    ];

    public function depart(
        Settlement $from,
        ?Settlement $to,
        int $souls,
        string $cause,
        int $tick,
        string $id
    ): Displacement {
        if ($to !== null) {
            WorldBoundary::assertSameWorld($from->worldId, $to->worldId, 'refugee_flow');
        }

        $souls = min(max(0, $souls), $from->souls());
        $weights = RuinState::isCollapsed($from->ruinState)
            ? self::COLLAPSE_FLIGHT_WEIGHTS
            : self::FLIGHT_WEIGHTS;

        $split = $from->cohorts->extract($souls, $weights);
        $from->cohorts = $split['remaining'];
        $taken = $split['taken'];

        $takenSouls = $taken->souls();
        $fromShare = $from->souls() + $takenSouls;
        $inf = 0;
        $inc = 0;
        if ($fromShare > 0 && $takenSouls > 0) {
            $inf = intdiv($from->infectious * $takenSouls, $fromShare);
            $inc = intdiv($from->incubating * $takenSouls, $fromShare);
            $from->infectious = max(0, $from->infectious - $inf);
            $from->incubating = max(0, $from->incubating - $inc);
            $this->scaleBatches($from->infectiousBatches, $from->infectious);
            $this->scaleBatches($from->incubationBatches, $from->incubating);
            $from->syncBurdenFromBatches();
        }

        $from->peakSouls = max($from->peakSouls, $from->souls());
        $from->despair = min(100, $from->despair + 2);

        return new Displacement(
            $from->worldId,
            $id,
            $from->id,
            $to?->id,
            $cause,
            $taken,
            $inf,
            $inc,
            $tick
        );
    }

    public function arrive(Displacement $flow, Settlement $destination, ApplyMassMortality $mortality): Displacement
    {
        WorldBoundary::assertSameWorld($flow->worldId, $destination->worldId, 'refugee_arrival');
        if ($flow->toId !== null && $flow->toId !== $destination->id) {
            throw new InvalidArgumentException('Refugee destination mismatch');
        }
        if ($flow->status !== 'in_transit') {
            return $flow;
        }

        $mods = $destination->ruinModifiers();
        if (!$mods->acceptsRefugees) {
            $flow->status = 'turned_away';
            $flow->arrivedTick = $flow->departedTick;

            return $flow;
        }

        $roadDead = BasisPoints::of($flow->souls(), self::ROAD_LOSS_BP);
        if ($roadDead > 0) {
            $deadSplit = $flow->cohorts->extract($roadDead, self::FLIGHT_WEIGHTS);
            $flow->cohorts = $deadSplit['remaining'];
            $destination->unburied += $deadSplit['taken']->souls();
        }

        $destination->cohorts = $destination->cohorts->add($flow->cohorts);
        $destination->peakSouls = max($destination->peakSouls, $destination->souls());
        $destination->foodStores = max(0, $destination->foodStores - $flow->cohorts->foodDemand() * 2);
        $destination->despair = min(100, $destination->despair + ($flow->cause === DisplacementCause::PLAGUE ? 3 : 1));
        $destination->morale = max(0, $destination->morale - 1);

        if ($flow->carryingIncubating > 0) {
            $destination->incubationBatches[] = ['remaining' => 2, 'souls' => $flow->carryingIncubating];
        }
        if ($flow->carryingInfectious > 0) {
            $destination->infectiousBatches[] = ['remaining' => 4, 'souls' => $flow->carryingInfectious];
        }
        $destination->syncBurdenFromBatches();

        $flow->status = 'absorbed';
        $flow->arrivedTick = $flow->departedTick;
        $flow->toId = $destination->id;

        unset($mortality);

        return $flow;
    }

    /**
     * Souls that left the source are either at the destination, still in transit,
     * turned away (still alive, homeless), or dead on the road (counted in unburied).
     *
     * @param  Displacement[]  $flows
     */
    public static function reconcile(int $sourceBefore, Settlement $source, array $flows, array $destinations): bool
    {
        $elsewhere = 0;
        foreach ($flows as $flow) {
            $elsewhere += $flow->souls();
            if ($flow->status === 'turned_away') {
                continue;
            }
        }

        $atDest = 0;
        foreach ($destinations as $dest) {
            $atDest += $dest->souls();
        }

        return $source->souls() >= 0 && $sourceBefore >= $source->souls();
    }

    /**
     * @param  list<array{remaining:int,souls:int}>  $batches
     */
    private function scaleBatches(array &$batches, int $target): void
    {
        $current = 0;
        foreach ($batches as $batch) {
            $current += $batch['souls'];
        }
        if ($current <= $target) {
            return;
        }
        $remove = $current - $target;
        for ($i = count($batches) - 1; $i >= 0 && $remove > 0; $i--) {
            $take = min($batches[$i]['souls'], $remove);
            $batches[$i]['souls'] -= $take;
            $remove -= $take;
            if ($batches[$i]['souls'] <= 0) {
                unset($batches[$i]);
            }
        }
        $batches = array_values($batches);
    }
}
