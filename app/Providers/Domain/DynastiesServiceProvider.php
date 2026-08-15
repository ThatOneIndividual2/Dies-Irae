<?php

namespace App\Providers\Domain;

use App\Domain\Dynasties\DynastiesContract;
use App\Domain\Dynasties\DynastiesService;
use Illuminate\Support\ServiceProvider;

class DynastiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DynastiesContract::class, DynastiesService::class);
    }
}
