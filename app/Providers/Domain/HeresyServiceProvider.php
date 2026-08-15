<?php

namespace App\Providers\Domain;

use App\Domain\Heresy\FractureEngine;
use App\Domain\Heresy\FracturePolicy;
use App\Domain\Heresy\HeresyContract;
use App\Domain\Heresy\HeresyService;
use App\Domain\Heresy\SpreadVectorGate;
use Illuminate\Support\ServiceProvider;

class HeresyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FracturePolicy::class);
        $this->app->singleton(SpreadVectorGate::class);
        $this->app->singleton(FractureEngine::class);
        $this->app->singleton(HeresyContract::class, HeresyService::class);
    }
}
