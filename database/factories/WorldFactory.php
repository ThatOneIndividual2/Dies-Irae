<?php

namespace Database\Factories;

use App\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorldFactory extends Factory
{
    protected $model = World::class;

    public function definition(): array
    {
        $name = 'Test World '.$this->faker->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numerify('###'),
            'current_date' => '1347-10-01',
            'start_date' => '1347-10-01',
            'status' => 'running',
            'game_speed' => 2,
            'simulation_seed' => 'test-seed',
        ];
    }
}
