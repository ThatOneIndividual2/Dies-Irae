<?php

namespace Database\Seeders;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\World\CreateWorld;
use App\Models\Territory;
use App\Models\World;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (class_exists(FixtureWorldSeeder::class)) {
            $this->call(FixtureWorldSeeder::class);
        }

        if (! World::query()->where('slug', 'provence-1347')->exists()) {
            app(SeedVerticalSlice::class)->execute();
        }

        $this->call(HistoricalWorldSeeder::class);

        if (! World::query()->where('slug', 'europa')->exists() && ! World::query()->exists()) {
            $world = app(CreateWorld::class)->execute([
                'name' => 'Europa',
                'slug' => 'europa',
                'start_date' => '1347-10-01',
            ]);

            Territory::query()->create([
                'world_id' => $world->id,
                'key' => 'marseille',
                'name' => 'Marseille',
            ]);
        }
    }
}
