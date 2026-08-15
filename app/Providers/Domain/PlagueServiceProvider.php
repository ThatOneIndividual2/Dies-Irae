<?php

namespace App\Providers\Domain;

use App\Domain\Plague\PlagueContract;
use App\Domain\Plague\PlagueService;
use Illuminate\Support\ServiceProvider;

class PlagueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlagueContract::class, PlagueService::class);
    }
}
