<?php

namespace App\Actions\Events;

use App\Actions\Time\ScheduleWorldEvent;
use App\Domain\Events\DeterministicPicker;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventEligibility;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Support\Transactional;
use App\Models\EventChainLink;
use App\Models\EventHook;
use App\Models\World;
use Carbon\Carbon;

final class PulseNarrativeEvents
{
    public function __construct(
        private EventCatalog $catalog,
        private EventWorldViewFactory $views,
        private EventEligibility $eligibility,
        private DeterministicPicker $picker,
        private FireCatalogEvent $fire,
        private ProcessDelayedConsequence $delayed,
        private ScheduleWorldEvent $schedule,
        private EnsureEventPulse $ensure
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world, bool $force = false): array
    {
        return Transactional::run(function () use ($world, $force) {
            $this->ensure->execute($world);
            $date = $world->current_date->toDateString();

            EventHook::query()
                ->where('world_id', $world->id)
                ->whereNotNull('expires_on')
                ->where('expires_on', '<', $date)
                ->delete();

            $chainResults = [];
            $dueLinks = EventChainLink::query()
                ->where('world_id', $world->id)
                ->whereIn('status', ['pending', 'waiting_state'])
                ->where(function ($q) use ($date) {
                    $q->whereNull('due_on')->orWhereDate('due_on', '<=', $date);
                })
                ->orderBy('id')
                ->get();
            foreach ($dueLinks as $link) {
                $link->status = 'pending';
                $link->save();
                $chainResults[] = $this->delayed->execute($link->fresh(), $force);
            }

            $view = $this->views->make($world);
            $maxPending = (int) config('events.max_pending_per_world', 6);
            $candidates = [];
            foreach ($this->catalog->definitions() as $definition) {
                foreach ($this->eligibility->candidatesFor($definition, $view, $maxPending) as $candidate) {
                    $candidates[] = $candidate;
                }
            }

            $limit = (int) config('events.max_new_events_per_pulse', 2);
            $picked = $this->picker->pick($candidates, $view->seed, $date, $limit);
            $fired = [];
            foreach ($picked as $candidate) {
                $event = $this->fire->execute($world, $candidate->key(), $candidate->scopeType, $candidate->scopeId, [
                    'weight' => $candidate->weight,
                    'actor_character_id' => $candidate->actorCharacterId,
                    'nonce' => 'pulse-'.$date.'-'.$candidate->key().'-'.$candidate->scopeId,
                ]);
                $fired[] = [
                    'id' => $event->id,
                    'key' => $candidate->key(),
                    'scope' => $candidate->scopeType.':'.$candidate->scopeId,
                    'weight' => $candidate->weight,
                    'status' => $event->status,
                ];
                $view = $this->views->make($world);
            }

            $interval = (int) config('events.pulse_interval_days', 1);
            $next = Carbon::parse($date)->addDays($interval);
            $this->schedule->execute(
                $world,
                'narrative_event_pulse',
                $next,
                ['reason' => 'pulse'],
                'narrative_pulse:'.$world->id.':'.$next->toDateString()
            );

            return [
                'date' => $date,
                'candidates' => count($candidates),
                'fired' => $fired,
                'chains' => $chainResults,
            ];
        });
    }
}
