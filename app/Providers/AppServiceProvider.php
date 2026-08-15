<?php

namespace App\Providers;

use App\Domain\Simulation\Handlers\CampaignBeatHandler;
use App\Domain\Simulation\Handlers\CampaignPulseHandler;
use App\Domain\Simulation\ScheduledWorldEventHandlerRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ScheduledWorldEventHandlerRegistry::class, function () {
            return new ScheduledWorldEventHandlerRegistry();
        });
    }

    public function boot()
    {
        $this->app->afterResolving(ScheduledWorldEventHandlerRegistry::class, function ($registry, $app) {
            if (!$registry->has('campaign_beat')) {
                $registry->register($app->make(CampaignBeatHandler::class));
            }
            if (!$registry->has('campaign_pulse')) {
                $registry->register($app->make(CampaignPulseHandler::class));
            }
        });
    }
}
