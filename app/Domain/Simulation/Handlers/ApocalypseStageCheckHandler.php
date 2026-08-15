<?php

namespace App\Domain\Simulation\Handlers;

use App\Actions\Apocalypse\ProcessApocalypseTick;
use App\Domain\Simulation\ScheduledWorldEventHandler;
use App\Models\ScheduledWorldEvent;
use App\Models\World;

final class ApocalypseStageCheckHandler implements ScheduledWorldEventHandler
{
    public function __construct(private ProcessApocalypseTick $tick)
    {
    }

    public function eventType(): string
    {
        return 'apocalypse_stage_check';
    }

    public function handle(ScheduledWorldEvent $event, array $payload): array
    {
        $world = World::query()->findOrFail($event->world_id);

        return $this->tick->execute($world);
    }
}
