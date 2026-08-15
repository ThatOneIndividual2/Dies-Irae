<?php

namespace App\Providers\Domain;

use App\Domain\Validation\ValidationContract;
use App\Domain\Validation\ValidationService;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ValidationContract::class, ValidationService::class);
    }
}
