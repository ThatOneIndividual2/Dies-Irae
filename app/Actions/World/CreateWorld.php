<?php

namespace App\Actions\World;

use App\Actions\Apocalypse\EnsureApocalypseState;
use App\Actions\Events\EnsureEventPulse;
use App\Domain\Support\Transactional;
use App\Models\World;
use Carbon\Carbon;

final class CreateWorld
{
    public function __invoke(array $attributes): World
    {
        return $this->execute($attributes);
    }

    public function execute(array|string $nameOrAttributes, array $attributes = []): World
    {
        if (is_string($nameOrAttributes)) {
            $attributes['name'] = $nameOrAttributes;
            $attributes['start_date'] = $attributes['start_date'] ?? $attributes['game_date'] ?? config('game.calendar.default_start_date');
        } else {
            $attributes = $nameOrAttributes;
        }

        return Transactional::run(function () use ($attributes) {
            $start = Carbon::parse($attributes['start_date'] ?? config('game.calendar.default_start_date'))->toDateString();
            $current = isset($attributes['game_date'])
                ? Carbon::parse($attributes['game_date'])->toDateString()
                : (isset($attributes['current_date'])
                    ? Carbon::parse($attributes['current_date'])->toDateString()
                    : $start);

            $world = World::query()->create([
                'slug' => $attributes['slug'] ?? strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $attributes['name'])),
                'name' => $attributes['name'],
                'status' => $attributes['status'] ?? 'running',
                'start_date' => $start,
                'game_date' => $current,
                'current_date' => $current,
                'game_speed' => $attributes['game_speed'] ?? 2,
                'simulation_seed' => (string) ($attributes['simulation_seed'] ?? bin2hex(random_bytes(8))),
                'political_map_version' => $attributes['political_map_version'] ?? 1,
            ]);

            app(EnsureApocalypseState::class)->execute($world);
            app(EnsureEventPulse::class)->execute($world);

            return $world->fresh(['apocalypseState']);
        });
    }
}
