<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\HellEventType;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\State\ThreatEvent;
use App\Domain\Hell\State\ThreatWorld;

final class NeighborPressure
{
    public function __construct(private IncursionLifecycle $lifecycle)
    {
    }

    /**
     * @return list<ThreatEvent>
     */
    public function apply(ThreatWorld $world): array
    {
        $events = [];
        $leaks = [];

        foreach ($world->territoryIdsSorted() as $id) {
            $t = $world->territory($id);
            $leak = $this->lifecycle->neighborLeak($t->incursionState);
            $scaled = (int) floor($leak * (0.35 + $world->apocalypseIntensity / 150));
            if ($scaled <= 0) {
                continue;
            }
            $leaks[$id] = $scaled;
        }

        foreach ($leaks as $sourceId => $amount) {
            $source = $world->territory($sourceId);
            $neighbors = $world->neighbors($sourceId);
            sort($neighbors, SORT_STRING);
            foreach ($neighbors as $nid) {
                $n = $world->territory($nid);
                $n->corruption = min(100, $n->corruption + $amount);
                $n->morale = max(0, $n->morale - (int) ceil($amount / 2));
                $n->despair = min(100, $n->despair + (int) ceil($amount / 3));

                if (IncursionState::isAtLeast($source->incursionState, IncursionState::BREACHED)) {
                    $n->travelOpen = false;
                }

                $catastrophicNeighbor = IncursionState::isAtLeast(
                    $source->incursionState,
                    IncursionState::OVERRUN
                );
                if (
                    $n->incursionState === IncursionState::DORMANT
                    && ($catastrophicNeighbor || $n->corruption >= 20 || $n->despair >= 25)
                ) {
                    $n->incursionState = IncursionState::TEMPTED;
                    $events[] = new ThreatEvent(
                        HellEventType::NEIGHBOR_TEMPTED,
                        $n->id,
                        "Corruption leaking from {$sourceId} tempts {$nid}.",
                        ['from' => $sourceId]
                    );
                }
            }
        }

        return $events;
    }
}
