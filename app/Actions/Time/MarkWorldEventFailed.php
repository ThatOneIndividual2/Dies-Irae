<?php

namespace App\Actions\Time;

use App\Models\ScheduledWorldEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class MarkWorldEventFailed
{
    public function execute(ScheduledWorldEvent $event, string $message, bool $retryable = true): ScheduledWorldEvent
    {
        $maxAttempts = (int) config('game.calendar.max_event_attempts', 5);

        return DB::transaction(function () use ($event, $message, $retryable, $maxAttempts) {
            $event = ScheduledWorldEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $event->failure_message = mb_substr($message, 0, 2000);
            $event->locked_at = null;
            $event->locked_by = null;

            if ($retryable && (int) $event->attempts < $maxAttempts) {
                $event->status = 'pending';
                $event->failed_at = null;
            } else {
                $event->status = 'failed';
                $event->failed_at = Carbon::now();
            }

            $event->save();

            return $event->fresh();
        });
    }
}
