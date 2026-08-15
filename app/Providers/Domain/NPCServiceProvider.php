<?php

namespace App\Providers\Domain;

use App\Domain\Ai\StrategicAiEngine;
use App\Domain\NPC\NPCContract;
use App\Domain\NPC\NPCService;
use Illuminate\Support\ServiceProvider;

class NPCServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StrategicAiEngine::class, function () {
            return StrategicAiEngine::fromDataDirectory((string) config('ai.data_path'));
        });

        $this->app->singleton(NPCContract::class, NPCService::class);
    }
}
