<?php

namespace App\Providers\Domain;

use App\Domain\SinVirtue\SinVirtueContract;
use App\Domain\SinVirtue\SinVirtueService;
use Illuminate\Support\ServiceProvider;

class SinVirtueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SinVirtueContract::class, SinVirtueService::class);
    }
}
