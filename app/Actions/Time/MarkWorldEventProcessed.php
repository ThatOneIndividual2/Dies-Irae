<?php

namespace App\Actions\Time;

use App\Models\ScheduledWorldEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class MarkWorldEventProcessed
{
    public function execute(ScheduledWorldEvent $event, ?array $resultMeta = null): ScheduledWorldEvent
    {
        return DB::transaction(function () use ($event, $resultMeta) {
            $event = ScheduledWorldEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $payload = $event->payload ?? [];
            if ($resultMeta !== null) {
                $payload['_result'] = $resultMeta;
            }

            $event->status = 'processed';
            $event->processed_at = Carbon::now();
            $event->locked_at = null;
            $event->locked_by = null;
            $event->payload = $payload;
            $event->failed_at = null;
            $event->failure_message = null;
            $event->save();

            return $event->fresh();
        });
    }
}
