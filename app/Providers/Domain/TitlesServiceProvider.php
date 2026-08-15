<?php

namespace App\Providers\Domain;

use App\Domain\Titles\TitlesContract;
use App\Domain\Titles\TitlesService;
use Illuminate\Support\ServiceProvider;

class TitlesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TitlesContract::class, TitlesService::class);
    }
}
