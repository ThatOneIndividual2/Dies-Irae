<?php

namespace App\Actions\Time;

use App\Models\ScheduledWorldEvent;
use App\Models\World;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClaimDueWorldEvents
{
    public function execute(
        World $world,
        ?CarbonInterface $asOf = null,
        ?int $limit = null,
        ?string $lockedBy = null
    ): Collection {
        $asOf = $asOf ? Carbon::parse($asOf->toDateString()) : Carbon::parse($world->current_date->toDateString());
        $limit = $limit ?? (int) config('game.calendar.max_events_per_batch', 50);
        $lockedBy = $lockedBy ?: ('worker-'.Str::random(8));
        $now = Carbon::now();

        return DB::transaction(function () use ($world, $asOf, $limit, $lockedBy, $now) {
            $ids = ScheduledWorldEvent::query()
                ->where('world_id', $world->id)
                ->where('status', 'pending')
                ->whereDate('process_at', '<=', $asOf->toDateString())
                ->orderBy('process_at')
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return collect();
            }

            ScheduledWorldEvent::query()
                ->whereIn('id', $ids)
                ->update([
                    'status' => 'processing',
                    'locked_at' => $now,
                    'locked_by' => $lockedBy,
                ]);

            ScheduledWorldEvent::query()->whereIn('id', $ids)->increment('attempts');

            return ScheduledWorldEvent::query()
                ->whereIn('id', $ids)
                ->orderBy('process_at')
                ->orderBy('id')
                ->get();
        });
    }
}
