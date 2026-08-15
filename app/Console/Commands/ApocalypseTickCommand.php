<?php

namespace App\Console\Commands;

use App\Actions\Apocalypse\ProcessApocalypseTick;
use App\Domain\Support\LocalInspectGuard;
use App\Models\World;
use Illuminate\Console\Command;

class ApocalypseTickCommand extends Command
{
    protected $signature = 'diesirae:apocalypse-tick
        {world? : World name}
        {--force : Tick even if this world date already ticked}';

    protected $description = 'Run one apocalypse tick (local/testing only)';

    public function handle(ProcessApocalypseTick $tick): int
    {
        LocalInspectGuard::assertMutable();

        $name = $this->argument('world') ?: config('game.default_world_name');
        $world = World::query()->where('name', $name)->orWhere('slug', $name)->first();
        if (!$world) {
            $this->error('World not found.');
            return self::FAILURE;
        }

        $result = $tick->execute($world, (bool) $this->option('force'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
