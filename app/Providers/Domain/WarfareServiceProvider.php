<?php

namespace App\Providers\Domain;

use App\Domain\Warfare\WarfareContract;
use App\Domain\Warfare\WarfareService;
use Illuminate\Support\ServiceProvider;

class WarfareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WarfareContract::class, WarfareService::class);
    }
}
