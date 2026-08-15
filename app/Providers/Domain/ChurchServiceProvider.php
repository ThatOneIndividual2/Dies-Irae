<?php

namespace App\Providers\Domain;

use App\Domain\Church\AppointmentPolicy;
use App\Domain\Church\ChurchAuthority;
use App\Domain\Church\ChurchContract;
use App\Domain\Church\ChurchEligibilityGate;
use App\Domain\Church\ChurchService;
use App\Domain\Church\ClergyLegitimacy;
use App\Domain\Church\DiocesanHierarchy;
use App\Domain\Church\SacramentalAuthorityResolver;
use App\Domain\Church\SpiritualOfficeHoldershipMutator;
use App\Domain\Succession\SuccessionEligibilityGate;
use Illuminate\Support\ServiceProvider;

class ChurchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppointmentPolicy::class);
        $this->app->singleton(ClergyLegitimacy::class);
        $this->app->singleton(DiocesanHierarchy::class);
        $this->app->singleton(SacramentalAuthorityResolver::class);
        $this->app->singleton(SpiritualOfficeHoldershipMutator::class);
        $this->app->singleton(ChurchAuthority::class);
        $this->app->singleton(ChurchEligibilityGate::class);
        $this->app->singleton(SuccessionEligibilityGate::class, ChurchEligibilityGate::class);
        $this->app->singleton(ChurchContract::class, ChurchService::class);
    }
}
