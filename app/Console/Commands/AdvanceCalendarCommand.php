<?php

namespace App\Console\Commands;

use App\Actions\Time\AdvanceWorldCalendar;
use App\Models\World;
use Illuminate\Console\Command;

class AdvanceCalendarCommand extends Command
{
    protected $signature = 'diesirae:process-calendar {slug=provence-1347} {--days=1}';
    protected $description = 'Advance the world calendar and open due campaign events';

    public function handle(AdvanceWorldCalendar $advance): int
    {
        $world = World::query()->where('slug', $this->argument('slug'))->first();
        if (!$world) {
            $this->error('World not found.');
            return self::FAILURE;
        }

        $world = $advance->execute($world, (int) $this->option('days'));
        $this->info('Date is now '.$world->current_date->toDateString());
        return self::SUCCESS;
    }
}
