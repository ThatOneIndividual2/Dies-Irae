<?php

namespace App\Domain\Simulation\Handlers;

use App\Actions\Catastrophe\TickPlague;
use App\Domain\Catastrophe\CatastropheEngine;

/**
 * Scheduled event type `territory_plague_tick`.
 * When the Laravel calendar exists, this handler is registered beside
 * character mortality. Until then the engine is the authority.
 */
final class TerritoryPlagueTickHandler
{
    public function eventType(): string
    {
        return 'territory_plague_tick';
    }

    /**
     * @param  object|null  $event
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function handle($event, array $payload): array
    {
        $engine = $payload['engine'] ?? null;
        if (!$engine instanceof CatastropheEngine) {
            return ['skipped' => true, 'reason' => 'engine_missing'];
        }

        $days = (int) ($payload['days'] ?? 1);

        return [
            'skipped' => false,
            'ticks' => (new TickPlague())->execute($engine, $days),
        ];
    }
}
