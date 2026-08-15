<?php

namespace App\Providers;

use App\Domain\Spiritual\Catalogs\SpiritualActCatalog;
use App\Domain\Spiritual\Ports\ChurchClericalAuthority;
use App\Domain\Spiritual\Ports\ClericalAuthorityPort;
use App\Domain\Spiritual\Ports\InMemoryClericalAuthority;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\DemonicInfluence;
use App\Models\Penance;
use App\Models\SacramentRecord;
use App\Models\Scandal;
use App\Models\SpiritualActRecord;
use App\Models\Temptation;
use Illuminate\Support\ServiceProvider;

class SpiritualServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/spiritual.php', 'spiritual');

        $this->app->singleton(SpiritualActCatalog::class, function () {
            return new SpiritualActCatalog(config('spiritual.act_effects', []));
        });
        $this->app->singleton(InMemoryClericalAuthority::class);
        $this->app->bind(ClericalAuthorityPort::class, function ($app) {
            if ($app->environment('testing')) {
                return $app->make(InMemoryClericalAuthority::class);
            }

            return $app->make(ChurchClericalAuthority::class);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views'), 'diesirae');

        if (!class_exists(Character::class)) {
            return;
        }

        Character::resolveRelationUsing('spiritualState', function (Character $character) {
            return $character->hasOne(CharacterSpiritualState::class)->where('is_current', true);
        });
        Character::resolveRelationUsing('canonicalState', function (Character $character) {
            return $character->hasOne(CharacterCanonicalState::class)->where('is_current', true);
        });
        Character::resolveRelationUsing('sacramentRecords', function (Character $character) {
            return $character->hasMany(SacramentRecord::class, 'subject_character_id');
        });
        Character::resolveRelationUsing('penances', function (Character $character) {
            return $character->hasMany(Penance::class);
        });
        Character::resolveRelationUsing('scandals', function (Character $character) {
            return $character->hasMany(Scandal::class);
        });
        Character::resolveRelationUsing('temptations', function (Character $character) {
            return $character->hasMany(Temptation::class);
        });
        Character::resolveRelationUsing('demonicInfluences', function (Character $character) {
            return $character->hasMany(DemonicInfluence::class);
        });
        Character::resolveRelationUsing('spiritualActs', function (Character $character) {
            return $character->hasMany(SpiritualActRecord::class);
        });
    }
}
