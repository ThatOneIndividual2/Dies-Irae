<?php

namespace App\Providers\Domain;

use App\Domain\Economy\EconomyContract;
use App\Domain\Economy\EconomyService;
use Illuminate\Support\ServiceProvider;

class EconomyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EconomyContract::class, EconomyService::class);
    }
}
