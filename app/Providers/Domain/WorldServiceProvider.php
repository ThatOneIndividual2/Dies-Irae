<?php

namespace App\Providers\Domain;

use App\Domain\World\WorldContract;
use App\Domain\World\WorldService;
use Illuminate\Support\ServiceProvider;

class WorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorldContract::class, WorldService::class);
    }
}
