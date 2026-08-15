<?php

namespace App\Actions\Events;

use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventEligibility;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Support\Transactional;
use App\Models\EventChainLink;
use App\Models\World;

final class ProcessDelayedConsequence
{
    public function __construct(
        private EventCatalog $catalog,
        private EventWorldViewFactory $views,
        private EventConditionEvaluator $conditions,
        private EventEligibility $eligibility,
        private ApplyEventEffects $effects,
        private FireCatalogEvent $fire
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(EventChainLink $link, bool $force = false): array
    {
        return Transactional::run(function () use ($link, $force) {
            $locked = EventChainLink::query()->whereKey($link->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                return ['skipped' => true, 'status' => $locked->status];
            }

            $world = World::query()->whereKey($locked->world_id)->lockForUpdate()->firstOrFail();
            $view = $this->views->make($world);
            $scopeType = (string) ($locked->scope_type ?: 'world');
            $scopeId = (int) ($locked->scope_id ?: $world->id);

            $when = $locked->when_clause ?? [];
            if (! $force && $when !== [] && ! $this->conditions->matches($when, $view, $scopeType, $scopeId)) {
                $locked->status = 'waiting_state';
                $locked->skip_reason = 'when_unmatched';
                $locked->save();

                return ['skipped' => true, 'reason' => 'when_unmatched', 'id' => $locked->id];
            }

            $applied = [];
            if (is_array($locked->ops) && $locked->ops !== []) {
                $applied = $this->effects->apply($world, $view, $locked->ops, [
                    'game_event_id' => $locked->from_event_id,
                    'definition_key' => $locked->to_definition_key,
                    'choice_key' => $locked->from_choice,
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'chain_key' => $locked->chain_key,
                    'refresh_view' => fn ($w) => $this->views->make($w),
                ], false);
                $view = $this->views->make($world);
            }

            $fired = null;
            if ($locked->to_definition_key) {
                $definition = $this->catalog->definition($locked->to_definition_key);
                $candidate = $this->eligibility->inspect(
                    $definition,
                    $view,
                    $scopeType,
                    $scopeId,
                    (int) config('events.max_pending_per_world', 6) + 8
                );
                if (! $force && $candidate->ineligibleReason !== '' && $candidate->ineligibleReason !== 'pending_cap') {
                    $locked->status = 'waiting_state';
                    $locked->skip_reason = $candidate->ineligibleReason;
                    $locked->save();

                    return ['skipped' => true, 'reason' => $candidate->ineligibleReason, 'applied' => $applied, 'id' => $locked->id];
                }
                $event = $this->fire->execute($world, $locked->to_definition_key, $scopeType, $scopeId, [
                    'view' => $this->views->make($world),
                    'weight' => $candidate->weight,
                    'parent_event_id' => $locked->from_event_id,
                    'chain_key' => $locked->chain_key,
                    'force' => $force,
                    'nonce' => 'chain-'.$locked->id,
                ]);
                $fired = $event->id;
            }

            $locked->status = 'processed';
            $locked->resolved_at = now();
            $locked->skip_reason = null;
            $locked->save();

            return ['skipped' => false, 'applied' => $applied, 'fired' => $fired, 'id' => $locked->id];
        });
    }
}
