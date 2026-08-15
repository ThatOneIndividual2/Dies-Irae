<?php

namespace App\Providers\Domain;

use App\Domain\Demons\DemonsContract;
use App\Domain\Demons\DemonsService;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\TaxonomyCatalog;
use Illuminate\Support\ServiceProvider;

class DemonsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DemonicThreatEngine::class, function () {
            return DemonicThreatEngine::fromDataDirectory((string) config('hell.data_path'));
        });

        $this->app->singleton(DemonsContract::class, DemonsService::class);
        $this->app->singleton(TaxonomyCatalog::class, function ($app) {
            return $app->make(DemonicThreatEngine::class)->catalog();
        });
    }
}
