<?php

namespace App\Providers;

use App\Models\Character;
use App\Models\User;
use App\Policies\SpiritualStatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Character::class => SpiritualStatePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
