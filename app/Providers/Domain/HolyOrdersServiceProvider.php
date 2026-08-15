<?php

namespace App\Providers\Domain;

use App\Domain\HolyOrders\ForceModifierCalculator;
use App\Domain\HolyOrders\HolyOrderContract;
use App\Domain\HolyOrders\HolyOrderOrganization;
use App\Domain\HolyOrders\HolyOrderService;
use App\Domain\HolyOrders\PoliticalPositionResolver;
use Illuminate\Support\ServiceProvider;

class HolyOrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ForceModifierCalculator::class);
        $this->app->singleton(PoliticalPositionResolver::class);
        $this->app->singleton(HolyOrderService::class);
        $this->app->singleton(HolyOrderContract::class, HolyOrderOrganization::class);
    }
}
