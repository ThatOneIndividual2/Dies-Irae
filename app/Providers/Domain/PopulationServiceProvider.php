<?php

namespace App\Providers\Domain;

use App\Domain\Population\PopulationContract;
use App\Domain\Population\PopulationService;
use Illuminate\Support\ServiceProvider;

class PopulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PopulationContract::class, PopulationService::class);
    }
}
