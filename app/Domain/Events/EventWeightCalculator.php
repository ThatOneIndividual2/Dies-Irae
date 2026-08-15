<?php

namespace App\Domain\Events;

final class EventWeightCalculator
{
    public function __construct(private EventConditionEvaluator $conditions)
    {
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function compute(array $definition, EventWorldView $view, string $scopeType, int $scopeId): int
    {
        $weight = (int) ($definition['weight'] ?? 10);
        foreach ($definition['weight_modifiers'] ?? [] as $modifier) {
            if (! is_array($modifier)) {
                continue;
            }
            $when = $modifier['when'] ?? [];
            if ($when !== [] && ! $this->conditions->matches($when, $view, $scopeType, $scopeId)) {
                continue;
            }
            $weight += (int) ($modifier['add'] ?? 0);
            if (isset($modifier['mul'])) {
                $weight = (int) round($weight * (float) $modifier['mul']);
            }
        }

        return max(0, $weight);
    }
}
