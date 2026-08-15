<?php

namespace App\Domain\Economy;

use App\Domain\Enums\SeasonPhase;

final class SeasonCalendar
{
    public int $year;
    public int $month;
    public string $phase;

    public function __construct(int $year = 1348, int $month = 9)
    {
        $this->year = $year;
        $this->month = max(1, min(12, $month));
        $this->phase = SeasonPhase::fromMonth($this->month);
    }

    public function advanceTo(string $phase): void
    {
        if ($phase === SeasonPhase::PLANTING && $this->phase === SeasonPhase::WINTER) {
            $this->year++;
        }
        $this->phase = $phase;
        $this->month = SeasonPhase::startMonth($phase);
    }

    public function advance(): string
    {
        $this->advanceTo(SeasonPhase::next($this->phase));

        return $this->phase;
    }
}
