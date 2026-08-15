<?php

namespace App\Domain\Apocalypse;

final class MilestoneEvaluator
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private ConditionEvaluator $conditions
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function newlyReached(WorldSnapshot $snapshot): array
    {
        $reached = [];

        foreach ($this->catalog->milestones() as $key => $milestone) {
            if ($snapshot->hasMilestone($key)) {
                continue;
            }

            $when = $milestone['when'] ?? null;
            if ($this->conditions->matches(is_array($when) ? $when : null, $snapshot)) {
                $reached[] = $milestone;
            }
        }

        return $reached;
    }
}
