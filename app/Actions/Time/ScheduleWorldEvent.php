<?php

namespace App\Actions\Time;

use App\Models\ScheduledWorldEvent;
use App\Models\World;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ScheduleWorldEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        World $world,
        string $eventType,
        CarbonInterface $processAt,
        array $payload = [],
        ?string $idempotencyKey = null
    ): ScheduledWorldEvent {
        $allowed = config('game.event_types', config('game.calendar.event_types', []));
        if ($allowed && !in_array($eventType, $allowed, true)) {
            throw new InvalidArgumentException("Unknown event type: {$eventType}");
        }

        return DB::transaction(function () use ($world, $eventType, $processAt, $payload, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = ScheduledWorldEvent::query()
                    ->where('world_id', $world->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            return ScheduledWorldEvent::query()->create([
                'world_id' => $world->id,
                'process_at' => Carbon::parse($processAt->toDateString())->toDateString(),
                'event_type' => $eventType,
                'status' => 'pending',
                'attempts' => 0,
                'payload' => $payload,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
}
