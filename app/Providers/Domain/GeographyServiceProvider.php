<?php

namespace App\Providers\Domain;

use App\Domain\Geography\GeographyContract;
use App\Domain\Geography\GeographyService;
use Illuminate\Support\ServiceProvider;

class GeographyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeographyContract::class, GeographyService::class);
    }
}
