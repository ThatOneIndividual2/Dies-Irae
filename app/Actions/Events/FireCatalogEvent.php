<?php

namespace App\Actions\Events;

use App\Domain\Enums\GameEventStatus;
use App\Domain\Events\AiChoiceSelector;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventWorldView;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Support\Transactional;
use App\Models\EventCooldown;
use App\Models\GameEvent;
use App\Models\World;
use Carbon\Carbon;

final class FireCatalogEvent
{
    public function __construct(
        private EventCatalog $catalog,
        private EventConditionEvaluator $conditions,
        private EventWorldViewFactory $views,
        private AiChoiceSelector $ai,
        private ResolveCatalogEvent $resolve
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(
        World $world,
        string $definitionKey,
        string $scopeType,
        int $scopeId,
        array $options = []
    ): GameEvent {
        return Transactional::run(function () use ($world, $definitionKey, $scopeType, $scopeId, $options) {
            $definition = $this->catalog->definition($definitionKey);
            $view = $options['view'] ?? $this->views->make($world);
            $actor = $options['actor_character_id'] ?? $this->conditions->actorId($view, $scopeType, $scopeId);
            $due = $options['due_on'] ?? $world->current_date->toDateString();
            $force = ! empty($options['force']);

            $occurrence = $options['occurrence_key'] ?? implode(':', [
                $definitionKey,
                $scopeType,
                $scopeId,
                $due,
                $options['nonce'] ?? bin2hex(random_bytes(3)),
            ]);

            $existing = GameEvent::query()
                ->where('world_id', $world->id)
                ->where('occurrence_key', $occurrence)
                ->first();
            if ($existing) {
                return $existing;
            }

            $choices = $this->visibleChoices($definition, $view, $scopeType, $scopeId);
            $optionMap = [];
            foreach ($choices as $key => $choice) {
                $optionMap[$key] = (string) ($choice['label'] ?? $key);
            }

            $visibility = (string) ($definition['visibility'] ?? 'player');
            $player = $actor && $view->isPlayer((int) $actor);
            $await = $visibility === 'player' && $player && empty($options['auto']);
            $status = $due > $world->current_date->toDateString()
                ? GameEventStatus::SCHEDULED
                : ($await ? GameEventStatus::AWAITING_DECISION : GameEventStatus::AWAITING_DECISION);

            $event = GameEvent::query()->create([
                'world_id' => $world->id,
                'event_key' => substr($definitionKey, 0, 40).'-'.substr(sha1($occurrence), 0, 12),
                'engine' => 'catalog',
                'definition_key' => $definitionKey,
                'category' => $definition['category'] ?? null,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'actor_character_id' => $actor,
                'visibility' => $visibility,
                'exclusivity_group' => $definition['exclusivity'] ?? null,
                'occurrence_key' => $occurrence,
                'chain_key' => $options['chain_key'] ?? ($definition['chain'] ?? null),
                'parent_event_id' => $options['parent_event_id'] ?? null,
                'weight_at_fire' => $options['weight'] ?? null,
                'title' => (string) ($definition['title'] ?? $definitionKey),
                'body' => (string) ($definition['body'] ?? ''),
                'status' => $status,
                'due_on' => $due,
                'options' => $optionMap,
                'payload' => [
                    'force' => $force,
                    'scope_name' => $this->scopeName($view, $scopeType, $scopeId),
                ],
            ]);

            $this->writeCooldown($world, $definition, $scopeType, $scopeId);

            $shouldAuto = $visibility !== 'player' || ! $player || ! empty($options['auto']);
            if ($shouldAuto && $status === GameEventStatus::AWAITING_DECISION && $optionMap !== []) {
                $choiceKey = $this->ai->choose($definition, $choices, $view, $scopeType, $scopeId, $view->seed, $occurrence);
                $this->resolve->execute($event->fresh(), $choiceKey);

                return $event->fresh();
            }

            return $event->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, array<string, mixed>>
     */
    public function visibleChoices(array $definition, EventWorldView $view, string $scopeType, int $scopeId): array
    {
        $out = [];
        foreach ($definition['choices'] ?? [] as $choice) {
            if (! is_array($choice) || empty($choice['key'])) {
                continue;
            }
            $when = $choice['visible_if'] ?? [];
            if ($when !== [] && ! $this->conditions->matches($when, $view, $scopeType, $scopeId)) {
                continue;
            }
            $out[$choice['key']] = $choice;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function writeCooldown(World $world, array $definition, string $scopeType, int $scopeId): void
    {
        $days = (int) ($definition['cooldown_days'] ?? 0);
        if ($days < 1) {
            return;
        }
        $mode = (string) ($definition['cooldown_scope'] ?? 'scope');
        $type = $mode === 'world' ? 'world' : $scopeType;
        $id = $mode === 'world' ? (int) $world->id : $scopeId;
        $available = Carbon::parse($world->current_date)->addDays($days)->toDateString();

        EventCooldown::query()->updateOrCreate(
            [
                'world_id' => $world->id,
                'definition_key' => $definition['key'],
                'scope_type' => $type,
                'scope_id' => $id,
            ],
            [
                'available_on' => $available,
                'last_fired_on' => $world->current_date->toDateString(),
            ]
        );
    }

    private function scopeName(EventWorldView $view, string $scopeType, int $scopeId): string
    {
        if ($scopeType === 'territory') {
            return (string) ($view->territory($scopeId)['name'] ?? $scopeId);
        }

        return $scopeType.'#'.$scopeId;
    }
}
