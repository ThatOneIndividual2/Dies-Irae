<?php

namespace App\Console\Commands;

use App\Actions\Time\ProcessDueWorldEvents;
use App\Models\World;
use Illuminate\Console\Command;

class ProcessWorldEventsCommand extends Command
{
    protected $signature = 'diesirae:process-world-events {world? : World name}';

    protected $description = 'Claim and process due scheduled world events';

    public function handle(ProcessDueWorldEvents $process): int
    {
        $query = World::query()->where('status', 'running');
        if ($this->argument('world')) {
            $query->where(function ($q) {
                $q->where('name', $this->argument('world'))
                    ->orWhere('slug', $this->argument('world'));
            });
        }

        $worlds = $query->get();
        if ($worlds->isEmpty()) {
            $this->warn('No running worlds.');
            return self::SUCCESS;
        }

        foreach ($worlds as $world) {
            $result = $process->execute($world);
            $this->line($world->name.': claimed '.$result['claimed'].' processed '.$result['processed'].' failed '.$result['failed']);
        }

        return self::SUCCESS;
    }
}
