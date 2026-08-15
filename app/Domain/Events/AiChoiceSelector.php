<?php

namespace App\Domain\Events;

final class AiChoiceSelector
{
    public function __construct(private EventConditionEvaluator $conditions)
    {
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $visibleChoices
     */
    public function choose(
        array $definition,
        array $visibleChoices,
        EventWorldView $view,
        string $scopeType,
        int $scopeId,
        string $seed,
        string $occurrenceKey
    ): string {
        $weights = [];
        foreach ($visibleChoices as $key => $choice) {
            $weights[$key] = $this->weight($choice, $view, $scopeType, $scopeId);
        }
        $total = array_sum($weights);
        if ($total < 1) {
            return (string) array_key_first($visibleChoices);
        }
        $roll = crc32($seed.'|ai|'.$occurrenceKey) % $total;
        $cursor = 0;
        foreach ($weights as $key => $weight) {
            $cursor += $weight;
            if ($roll < $cursor) {
                return (string) $key;
            }
        }

        return (string) array_key_last($visibleChoices);
    }

    /**
     * @param  array<string, mixed>  $choice
     */
    public function weight(array $choice, EventWorldView $view, string $scopeType, int $scopeId): int
    {
        $ai = $choice['ai'] ?? [];
        if (! is_array($ai)) {
            $ai = ['base' => (int) $ai];
        }
        $weight = (int) ($ai['base'] ?? 10);
        foreach ($ai['modifiers'] ?? [] as $modifier) {
            if (! is_array($modifier)) {
                continue;
            }
            $when = $modifier['when'] ?? [];
            if ($when !== [] && ! $this->conditions->matches($when, $view, $scopeType, $scopeId)) {
                continue;
            }
            $weight += (int) ($modifier['add'] ?? 0);
        }

        return max(0, $weight);
    }
}
