<?php

namespace App\Providers;

use App\Domain\Sacred\Ports\ChurchSacredAuthority;
use App\Domain\Sacred\Ports\EloquentPlagueTravelPort;
use App\Domain\Sacred\Ports\InMemorySacredAuthority;
use App\Domain\Sacred\Ports\PlagueTravelPort;
use App\Domain\Sacred\Ports\RecordingPlagueTravelPort;
use App\Domain\Sacred\Ports\SacredAuthorityPort;
use Illuminate\Support\ServiceProvider;

class SacredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/sacred.php', 'sacred');

        $this->app->singleton(InMemorySacredAuthority::class);
        $this->app->singleton(RecordingPlagueTravelPort::class);

        $this->app->bind(SacredAuthorityPort::class, function ($app) {
            if ($app->environment('testing')) {
                return $app->make(InMemorySacredAuthority::class);
            }

            return $app->make(ChurchSacredAuthority::class);
        });

        $this->app->bind(PlagueTravelPort::class, function ($app) {
            if ($app->environment('testing')) {
                return $app->make(RecordingPlagueTravelPort::class);
            }

            return $app->make(EloquentPlagueTravelPort::class);
        });
    }
}
