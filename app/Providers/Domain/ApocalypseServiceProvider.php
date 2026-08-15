<?php

namespace App\Providers\Domain;

use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Contracts\ApocalypseReporter;
use App\Domain\Apocalypse\AmbientDrift;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Apocalypse\ApocalypseContract;
use App\Domain\Apocalypse\ApocalypseService;
use App\Domain\Apocalypse\ConditionEvaluator;
use App\Domain\Apocalypse\LocalWeatherPolicy;
use App\Domain\Apocalypse\MeterBoundPolicy;
use App\Domain\Apocalypse\MilestoneEvaluator;
use App\Domain\Apocalypse\PhaseAdvanceEvaluator;
use App\Domain\Apocalypse\PressureCalculator;
use App\Domain\Apocalypse\SignalApplier;
use App\Domain\Hell\Ports\ApocalypseReading;
use App\Domain\Simulation\Handlers\ApocalypseStageCheckHandler;
use App\Domain\Simulation\ScheduledWorldEventHandlerRegistry;
use Illuminate\Support\ServiceProvider;

class ApocalypseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApocalypseCatalog::class, function () {
            $relative = config('apocalypse.data_path', 'database/data/apocalypse');
            $path = str_starts_with($relative, '/') ? $relative : base_path($relative);

            return ApocalypseCatalog::fromDirectory($path);
        });

        $this->app->singleton(PressureCalculator::class, function () {
            return PressureCalculator::fromConfig();
        });

        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(MeterBoundPolicy::class);
        $this->app->singleton(AmbientDrift::class);
        $this->app->singleton(LocalWeatherPolicy::class);

        $this->app->singleton(SignalApplier::class, function ($app) {
            return new SignalApplier($app->make(ApocalypseCatalog::class));
        });

        $this->app->singleton(PhaseAdvanceEvaluator::class, function ($app) {
            return new PhaseAdvanceEvaluator(
                $app->make(ApocalypseCatalog::class),
                $app->make(ConditionEvaluator::class)
            );
        });

        $this->app->singleton(MilestoneEvaluator::class, function ($app) {
            return new MilestoneEvaluator(
                $app->make(ApocalypseCatalog::class),
                $app->make(ConditionEvaluator::class)
            );
        });

        $this->app->singleton(ApocalypseReporter::class, RecordApocalypseSignal::class);
        $this->app->singleton(ApocalypseContract::class, ApocalypseService::class);
        $this->app->singleton(ApocalypseReading::class, ApocalypseService::class);

        $this->app->singleton(ScheduledWorldEventHandlerRegistry::class, function () {
            return new ScheduledWorldEventHandlerRegistry();
        });

        $this->app->afterResolving(ScheduledWorldEventHandlerRegistry::class, function ($registry, $app) {
            if (!$registry->has('apocalypse_stage_check')) {
                $registry->register($app->make(ApocalypseStageCheckHandler::class));
            }
        });
    }
}
