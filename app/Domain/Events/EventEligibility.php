<?php

namespace App\Domain\Events;

final class EventEligibility
{
    public function __construct(
        private EventConditionEvaluator $conditions,
        private EventWeightCalculator $weights,
        private EventScopeResolver $scopes
    ) {
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<EventCandidate>
     */
    public function candidatesFor(array $definition, EventWorldView $view, int $maxPending): array
    {
        $found = [];
        foreach ($this->scopes->candidates($definition, $view) as $scope) {
            $result = $this->inspect($definition, $view, $scope['type'], $scope['id'], $maxPending);
            if ($result->weight > 0 && $result->ineligibleReason === '') {
                $found[] = $result;
            }
        }

        return $found;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function inspect(
        array $definition,
        EventWorldView $view,
        string $scopeType,
        int $scopeId,
        int $maxPending
    ): EventCandidate {
        $actor = $this->conditions->actorId($view, $scopeType, $scopeId);
        $key = (string) $definition['key'];

        if ($view->pendingCount() >= $maxPending) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'pending_cap');
        }

        $trigger = $definition['trigger'] ?? [];
        if (! $this->conditions->matches($trigger, $view, $scopeType, $scopeId)) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'trigger');
        }

        $filter = $definition['candidate_filter'] ?? [];
        if ($filter !== [] && ! $this->conditions->matches($filter, $view, $scopeType, $scopeId)) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'filter');
        }

        $cooldownMode = (string) ($definition['cooldown_scope'] ?? 'scope');
        $cdType = $cooldownMode === 'world' ? 'world' : $scopeType;
        $cdId = $cooldownMode === 'world' ? $view->worldId : $scopeId;
        if (! $view->cooldownOpen($key, $cdType, $cdId)) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'cooldown');
        }

        $group = (string) ($definition['exclusivity'] ?? '');
        $exMode = (string) ($definition['exclusivity_scope'] ?? 'same_scope');
        if ($group !== '' && $view->exclusivityBlocked($group, $scopeType, $scopeId, $exMode === 'world' ? 'world' : 'scope')) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'exclusivity');
        }

        if (! empty($definition['once_per_scope']) && $view->alreadyFired($key, $scopeType, $scopeId)) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'once_per_scope');
        }

        $maxPendingDef = (int) ($definition['max_pending'] ?? 0);
        if ($maxPendingDef > 0) {
            $open = 0;
            foreach ($view->pendingEvents as $event) {
                if (($event['definition_key'] ?? '') === $key) {
                    $open++;
                }
            }
            if ($open >= $maxPendingDef) {
                return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'definition_pending_cap');
            }
        }

        $weight = $this->weights->compute($definition, $view, $scopeType, $scopeId);
        if ($weight <= 0) {
            return new EventCandidate($definition, $scopeType, $scopeId, 0, $actor, 'weight');
        }

        return new EventCandidate($definition, $scopeType, $scopeId, $weight, $actor);
    }
}
