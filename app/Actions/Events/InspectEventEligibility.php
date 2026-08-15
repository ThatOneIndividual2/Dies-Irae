<?php

namespace App\Actions\Events;

use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventEligibility;
use App\Domain\Events\EventWorldViewFactory;
use App\Models\EventCooldown;
use App\Models\EventHook;
use App\Models\World;

final class InspectEventEligibility
{
    public function __construct(
        private EventCatalog $catalog,
        private EventWorldViewFactory $views,
        private EventEligibility $eligibility
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world, ?string $definitionKey = null, ?string $scopeType = null, ?int $scopeId = null): array
    {
        $view = $this->views->make($world);
        $maxPending = (int) config('events.max_pending_per_world', 6);
        $rows = [];
        $definitions = $definitionKey
            ? [$this->catalog->definition($definitionKey)]
            : array_values($this->catalog->definitions());

        foreach ($definitions as $definition) {
            $scopes = $scopeType && $scopeId
                ? [['type' => $scopeType, 'id' => $scopeId]]
                : (new \App\Domain\Events\EventScopeResolver())->candidates($definition, $view);
            foreach ($scopes as $scope) {
                $candidate = $this->eligibility->inspect(
                    $definition,
                    $view,
                    $scope['type'],
                    $scope['id'],
                    $maxPending
                );
                $cd = EventCooldown::query()
                    ->where('world_id', $world->id)
                    ->where('definition_key', $definition['key'])
                    ->where('scope_type', $scope['type'])
                    ->where('scope_id', $scope['id'])
                    ->first();
                $rows[] = [
                    'key' => $definition['key'],
                    'scope_type' => $scope['type'],
                    'scope_id' => $scope['id'],
                    'weight' => $candidate->weight,
                    'reason' => $candidate->ineligibleReason === '' ? 'eligible' : $candidate->ineligibleReason,
                    'actor_character_id' => $candidate->actorCharacterId,
                    'cooldown_until' => $cd?->available_on?->toDateString(),
                ];
            }
        }

        $hooks = EventHook::query()->where('world_id', $world->id)->orderBy('id')->get();

        return [
            'date' => $view->date,
            'phase' => $view->phaseKey,
            'pending' => $view->pendingCount(),
            'rows' => $rows,
            'hooks' => $hooks,
        ];
    }
}
