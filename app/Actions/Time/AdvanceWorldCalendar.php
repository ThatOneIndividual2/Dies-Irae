<?php

namespace App\Actions\Time;

use App\Domain\Enums\GameEventStatus;
use App\Domain\Support\Transactional;
use App\Models\GameEvent;
use App\Models\World;
use Carbon\Carbon;
use InvalidArgumentException;

final class AdvanceWorldCalendar
{
    public function __construct(private ProcessDueWorldEvents $processEvents)
    {
    }
    public function execute(World $world, int $days = 1): World
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Calendar must advance at least one day.');
        }

        $cap = (int) config('game.calendar.catch_up_day_cap', 30);
        if ($days > $cap) {
            $days = $cap;
        }

        return Transactional::run(function () use ($world, $days) {
            $locked = World::query()->whereKey($world->id)->lockForUpdate()->firstOrFail();
            $date = Carbon::parse($locked->current_date);
            for ($i = 0; $i < $days; $i++) {
                $date->addDay();
                $locked->current_date = $date->toDateString();
                $locked->last_processed_at = now();
                $locked->save();
                $this->openDueEvents($locked, $date->toDateString());
            }

            $this->processEvents->execute($locked);

            return $locked->fresh();
        });
    }

    private function openDueEvents(World $world, string $date): void
    {
        GameEvent::query()
            ->where('world_id', $world->id)
            ->where('status', GameEventStatus::SCHEDULED)
            ->where('due_on', '<=', $date)
            ->update(['status' => GameEventStatus::AWAITING_DECISION]);
    }
}
