<?php

namespace App\Providers\Domain;

use App\Domain\Papacy\PapacyContract;
use App\Domain\Papacy\PapacyService;
use Illuminate\Support\ServiceProvider;

class PapacyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PapacyContract::class, PapacyService::class);
    }
}
