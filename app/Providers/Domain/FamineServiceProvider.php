<?php

namespace App\Providers\Domain;

use App\Domain\Famine\FamineContract;
use App\Domain\Famine\FamineService;
use Illuminate\Support\ServiceProvider;

class FamineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FamineContract::class, FamineService::class);
    }
}
