<?php

namespace App\Providers\Domain;

use App\Domain\Faith\FaithContract;
use App\Domain\Faith\FaithService;
use Illuminate\Support\ServiceProvider;

class FaithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FaithContract::class, FaithService::class);
    }
}
