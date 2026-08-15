<?php

namespace App\Actions\Events;

use App\Domain\Enums\GameEventStatus;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Support\Transactional;
use App\Models\GameEvent;
use InvalidArgumentException;
use RuntimeException;

final class ResolveCatalogEvent
{
    public function __construct(
        private EventCatalog $catalog,
        private EventWorldViewFactory $views,
        private ApplyEventEffects $effects
    ) {
    }

    public function execute(GameEvent $event, string $option): GameEvent
    {
        if ($event->status !== GameEventStatus::AWAITING_DECISION) {
            throw new RuntimeException('This event is not awaiting a decision.');
        }

        $key = (string) ($event->definition_key ?: $event->event_key);
        $definition = $this->catalog->definition($key);
        $choice = null;
        foreach ($definition['choices'] ?? [] as $row) {
            if (($row['key'] ?? '') === $option) {
                $choice = $row;
                break;
            }
        }
        if ($choice === null) {
            throw new InvalidArgumentException("Unknown option {$option} for {$key}");
        }

        return Transactional::run(function () use ($event, $option, $definition, $choice, $key) {
            $locked = GameEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $world = $locked->world()->lockForUpdate()->firstOrFail();
            $view = $this->views->make($world);
            $context = [
                'game_event_id' => $locked->id,
                'definition_key' => $key,
                'choice_key' => $option,
                'scope_type' => $locked->scope_type ?: 'world',
                'scope_id' => (int) ($locked->scope_id ?: $world->id),
                'actor_character_id' => $locked->actor_character_id,
                'chain_key' => $locked->chain_key ?: ($definition['chain'] ?? $key),
                'refresh_view' => fn ($w) => $this->views->make($w),
            ];

            $immediate = $this->effects->apply($world, $view, $choice['immediate'] ?? [], $context, false);
            $view = $this->views->make($world);
            $hidden = $this->effects->apply($world, $view, $choice['hidden'] ?? [], $context, true);
            $view = $this->views->make($world);
            $delayed = $this->effects->apply($world, $view, $this->delayedAsQueue($choice['delayed'] ?? []), $context, false);

            $locked->status = GameEventStatus::RESOLVED;
            $locked->chosen_option = $option;
            $locked->effects = [
                'immediate' => $immediate,
                'delayed' => $delayed,
            ];
            $locked->payload = array_merge($locked->payload ?? [], ['hidden_count' => count($hidden)]);
            $locked->resolved_at = now();
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * Delayed ops become spawn/queue ops with days preserved.
     *
     * @param  list<array<string, mixed>>  $delayed
     * @return list<array<string, mixed>>
     */
    private function delayedAsQueue(array $delayed): array
    {
        $ops = [];
        foreach ($delayed as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['op'] ?? '') === 'spawn_event') {
                $ops[] = $row;
                continue;
            }
            $ops[] = [
                'op' => 'spawn_event',
                'days' => (int) ($row['days'] ?? 1),
                'when' => $row['when'] ?? null,
                'ops' => [[
                    'op' => $row['op'],
                    'delta' => $row['delta'] ?? null,
                    'hook' => $row['hook'] ?? null,
                    'intensity' => $row['intensity'] ?? null,
                    'ttl_days' => $row['ttl_days'] ?? null,
                    'signal' => $row['signal'] ?? null,
                    'magnitude' => $row['magnitude'] ?? null,
                    'souls' => $row['souls'] ?? null,
                    'source' => $row['source'] ?? null,
                    'event' => $row['event'] ?? null,
                    'when' => $row['when'] ?? null,
                ]],
            ];
        }

        return $ops;
    }
}
