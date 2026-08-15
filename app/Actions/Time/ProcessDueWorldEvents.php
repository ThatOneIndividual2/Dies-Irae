<?php

namespace App\Actions\Time;

use App\Models\World;

final class ProcessDueWorldEvents
{
    public function __construct(
        private ClaimDueWorldEvents $claimDue,
        private ProcessScheduledWorldEvent $processEvent
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world, ?int $limit = null): array
    {
        $claimed = $this->claimDue->execute($world, $world->current_date, $limit);
        $processed = 0;
        $failed = 0;
        $results = [];

        foreach ($claimed as $event) {
            $result = $this->processEvent->execute($event);
            $results[] = $result;
            if (!empty($result['failed'])) {
                $failed++;
            } else {
                $processed++;
            }
        }

        return [
            'claimed' => $claimed->count(),
            'processed' => $processed,
            'failed' => $failed,
            'results' => $results,
        ];
    }
}
