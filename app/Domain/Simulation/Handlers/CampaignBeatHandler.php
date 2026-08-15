<?php

namespace App\Domain\Simulation\Handlers;

use App\Domain\Simulation\ScheduledWorldEventHandler;
use App\Models\ScheduledWorldEvent;

final class CampaignBeatHandler implements ScheduledWorldEventHandler
{
    public function eventType(): string
    {
        return 'campaign_beat';
    }

    public function handle(ScheduledWorldEvent $event, array $payload): array
    {
        return [
            'ok' => true,
            'event_key' => $payload['event_key'] ?? null,
        ];
    }
}
