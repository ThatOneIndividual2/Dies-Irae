<?php

namespace App\Actions\Time;

use App\Domain\Simulation\ScheduledWorldEventHandlerRegistry;
use App\Models\ScheduledWorldEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessScheduledWorldEvent
{
    public function __construct(
        private ScheduledWorldEventHandlerRegistry $registry,
        private MarkWorldEventProcessed $markProcessed,
        private MarkWorldEventFailed $markFailed
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(ScheduledWorldEvent $event): array
    {
        try {
            if ($event->status === 'cancelled' || $event->status === 'processed') {
                return ['skipped' => true, 'status' => $event->status];
            }

            $handler = $this->registry->get($event->event_type);
            $result = $handler->handle($event, $event->payload ?? []);

            if (!empty($result['cancelled'])) {
                $event->status = 'cancelled';
                $event->processed_at = now();
                $event->locked_at = null;
                $event->locked_by = null;
                $payload = $event->payload ?? [];
                $payload['_result'] = $result;
                $event->payload = $payload;
                $event->save();

                return $result;
            }

            $this->markProcessed->execute($event, $result);

            return $result;
        } catch (Throwable $e) {
            Log::warning('Scheduled world event failed', [
                'event_id' => $event->id,
                'type' => $event->event_type,
                'message' => $e->getMessage(),
            ]);
            $this->markFailed->execute($event, $e->getMessage());

            return ['failed' => true, 'message' => $e->getMessage()];
        }
    }
}
