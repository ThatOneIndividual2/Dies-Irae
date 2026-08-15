<?php

namespace App\Console\Commands;

use App\Actions\Hell\EnsureNamedInfernalFixtures;
use App\Models\World;
use Illuminate\Console\Command;

class EnsureNamedInfernalFixturesCommand extends Command
{
    protected $signature = 'diesirae:ensure-named-infernal {world? : World slug}';

    protected $description = 'Idempotent named infernal fixtures (does not reset worlds)';

    public function handle(EnsureNamedInfernalFixtures $ensure): int
    {
        $slug = $this->argument('world') ?? 'provence-1347';
        $world = World::query()->where('slug', $slug)->first();
        if ($world === null) {
            $this->error("World not found: {$slug}");

            return self::FAILURE;
        }

        $ensure->execute($world);
        $this->info("Named infernal fixtures present on {$world->slug}.");

        return self::SUCCESS;
    }
}
