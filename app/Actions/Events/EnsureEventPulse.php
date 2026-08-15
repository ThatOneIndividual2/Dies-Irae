<?php

namespace App\Actions\Events;

use App\Actions\Time\ScheduleWorldEvent;
use App\Domain\Support\Transactional;
use App\Models\ScheduledWorldEvent;
use App\Models\World;
use Carbon\Carbon;

final class EnsureEventPulse
{
    public function __construct(private ScheduleWorldEvent $schedule)
    {
    }

    public function execute(World $world): void
    {
        Transactional::run(function () use ($world) {
            $exists = ScheduledWorldEvent::query()
                ->where('world_id', $world->id)
                ->where('event_type', 'narrative_event_pulse')
                ->where('status', 'pending')
                ->exists();
            if ($exists) {
                return;
            }

            $interval = (int) config('events.pulse_interval_days', 1);
            $date = Carbon::parse($world->current_date)->addDays($interval);
            $this->schedule->execute(
                $world,
                'narrative_event_pulse',
                $date,
                ['reason' => 'initial'],
                'narrative_pulse:'.$world->id.':'.$date->toDateString()
            );
        });
    }
}
