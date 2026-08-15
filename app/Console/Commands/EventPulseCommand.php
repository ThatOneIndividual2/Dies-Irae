<?php

namespace App\Console\Commands;

use App\Actions\Events\PulseNarrativeEvents;
use App\Domain\Support\LocalInspectGuard;
use App\Models\World;
use Illuminate\Console\Command;

class EventPulseCommand extends Command
{
    protected $signature = 'diesirae:event-pulse {world? : World name} {--force}';

    protected $description = 'Run the narrative event pulse (local/testing only)';

    public function handle(PulseNarrativeEvents $pulse): int
    {
        LocalInspectGuard::assertMutable();
        $name = $this->argument('world') ?: config('game.default_world_name');
        $world = World::query()->where('name', $name)->orWhere('slug', $name)->first();
        if (! $world) {
            $this->error('World not found.');

            return self::FAILURE;
        }
        $result = $pulse->execute($world, (bool) $this->option('force'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
