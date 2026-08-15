<?php

namespace App\Domain\Simulation;

interface ScheduledWorldEventHandler
{
    public function eventType(): string;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(\App\Models\ScheduledWorldEvent $event, array $payload): array;
}
