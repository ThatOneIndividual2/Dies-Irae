<?php

namespace App\Providers\Domain;

use App\Domain\Careers\CareerEngine;
use App\Domain\Careers\CareerNpcAdvisor;
use App\Domain\Characters\CharactersContract;
use App\Domain\Characters\CharactersService;
use Illuminate\Support\ServiceProvider;

class CharactersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CharactersContract::class, CharactersService::class);
        $this->app->singleton(CareerEngine::class, CareerEngine::class);
        $this->app->singleton(CareerNpcAdvisor::class, CareerNpcAdvisor::class);
    }
}
