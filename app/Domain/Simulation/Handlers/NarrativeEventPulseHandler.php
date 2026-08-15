<?php

namespace App\Domain\Simulation\Handlers;

use App\Actions\Events\PulseNarrativeEvents;
use App\Domain\Simulation\ScheduledWorldEventHandler;
use App\Models\ScheduledWorldEvent;
use App\Models\World;

final class NarrativeEventPulseHandler implements ScheduledWorldEventHandler
{
    public function __construct(private PulseNarrativeEvents $pulse)
    {
    }

    public function eventType(): string
    {
        return 'narrative_event_pulse';
    }

    public function handle(ScheduledWorldEvent $event, array $payload): array
    {
        $world = World::query()->findOrFail($event->world_id);

        return $this->pulse->execute($world);
    }
}
