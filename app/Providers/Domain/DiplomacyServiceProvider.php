<?php

namespace App\Providers\Domain;

use App\Domain\Diplomacy\DiplomacyContract;
use App\Domain\Diplomacy\DiplomacyService;
use Illuminate\Support\ServiceProvider;

class DiplomacyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DiplomacyContract::class, DiplomacyService::class);
    }
}
