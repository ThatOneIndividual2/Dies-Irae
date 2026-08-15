<?php

namespace App\Console\Commands;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Models\World;
use Illuminate\Console\Command;

class SeedVerticalSliceCommand extends Command
{
    protected $signature = 'diesirae:seed-slice {--force}';
    protected $description = 'Seed the Provence 1347 vertical slice (does not touch other games)';

    public function handle(SeedVerticalSlice $seed): int
    {
        if (World::query()->where('slug', 'provence-1347')->exists() && !$this->option('force')) {
            $this->warn('Slice already exists. Pass --force after truncating this database only.');
            return self::SUCCESS;
        }

        $seed->execute();
        $this->info('Seeded. Login: lord@diesirae.test / password');
        return self::SUCCESS;
    }
}
