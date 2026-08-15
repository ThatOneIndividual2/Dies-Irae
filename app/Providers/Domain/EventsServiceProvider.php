<?php

namespace App\Providers\Domain;

use App\Domain\Events\AiChoiceSelector;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventDefinitionValidator;
use App\Domain\Events\EventEligibility;
use App\Domain\Events\EventsContract;
use App\Domain\Events\EventsService;
use App\Domain\Events\EventScopeResolver;
use App\Domain\Events\EventWeightCalculator;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Simulation\Handlers\NarrativeEventPulseHandler;
use App\Domain\Simulation\ScheduledWorldEventHandlerRegistry;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventCatalog::class, function () {
            $relative = config('events.data_path', 'database/data/events');
            $path = str_starts_with($relative, '/') ? $relative : base_path($relative);

            return EventCatalog::fromDirectory($path);
        });

        $this->app->singleton(EventConditionEvaluator::class);
        $this->app->singleton(EventWeightCalculator::class);
        $this->app->singleton(EventScopeResolver::class);
        $this->app->singleton(EventEligibility::class);
        $this->app->singleton(AiChoiceSelector::class);
        $this->app->singleton(EventWorldViewFactory::class);
        $this->app->singleton(EventDefinitionValidator::class);
        $this->app->singleton(EventsContract::class, EventsService::class);

        $this->app->extend(ScheduledWorldEventHandlerRegistry::class, function ($registry, $app) {
            if (! $registry->has('narrative_event_pulse')) {
                $registry->register($app->make(NarrativeEventPulseHandler::class));
            }

            return $registry;
        });
    }
}
