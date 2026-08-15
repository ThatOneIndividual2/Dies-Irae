<?php

namespace App\Providers\Domain;

use App\Domain\Corruption\CorruptionContract;
use App\Domain\Corruption\CorruptionService;
use Illuminate\Support\ServiceProvider;

class CorruptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CorruptionContract::class, CorruptionService::class);
    }
}
