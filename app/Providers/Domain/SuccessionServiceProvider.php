<?php

namespace App\Providers\Domain;

use App\Domain\Succession\SuccessionContract;
use App\Domain\Succession\SuccessionService;
use Illuminate\Support\ServiceProvider;

class SuccessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SuccessionContract::class, SuccessionService::class);
    }
}
