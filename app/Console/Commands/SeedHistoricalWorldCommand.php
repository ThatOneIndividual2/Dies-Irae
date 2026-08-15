<?php

namespace App\Console\Commands;

use App\Domain\World\HistoricalPack;
use App\Models\World;
use Database\Seeders\HistoricalWorldSeeder;
use Illuminate\Console\Command;

class SeedHistoricalWorldCommand extends Command
{
    protected $signature = 'diesirae:seed-historical';

    protected $description = 'Seed Europa 1347 without touching lys-1348 or provence-1347';

    public function handle(): int
    {
        if (World::query()->where('slug', HistoricalPack::SLUG)->exists()) {
            $this->warn(HistoricalPack::SLUG.' already exists. No worlds were deleted.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => HistoricalWorldSeeder::class]);
        $this->info('Seeded '.HistoricalPack::SLUG.'.');

        return self::SUCCESS;
    }
}
