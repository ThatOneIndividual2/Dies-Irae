<?php

namespace App\Providers\Domain;

use App\Domain\Time\TimeContract;
use App\Domain\Time\TimeService;
use Illuminate\Support\ServiceProvider;

class TimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TimeContract::class, TimeService::class);
    }
}
