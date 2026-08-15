<?php

namespace App\Providers\Domain;

use App\Domain\Realms\RealmsContract;
use App\Domain\Realms\RealmsService;
use Illuminate\Support\ServiceProvider;

class RealmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RealmsContract::class, RealmsService::class);
    }
}
