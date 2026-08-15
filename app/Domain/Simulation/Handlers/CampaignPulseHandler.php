<?php

namespace App\Domain\Simulation\Handlers;

use App\Actions\Campaign\RunCampaignPulse;
use App\Domain\Simulation\ScheduledWorldEventHandler;
use App\Models\ScheduledWorldEvent;
use App\Models\World;

final class CampaignPulseHandler implements ScheduledWorldEventHandler
{
    public function __construct(private RunCampaignPulse $pulse)
    {
    }

    public function eventType(): string
    {
        return 'campaign_pulse';
    }

    public function handle(ScheduledWorldEvent $event, array $payload): array
    {
        $world = World::query()->findOrFail($event->world_id);

        return $this->pulse->execute($world);
    }
}
