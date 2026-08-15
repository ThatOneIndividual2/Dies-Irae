<?php

namespace App\Models\Concerns;

use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\DemonicInfluence;
use App\Models\Penance;
use App\Models\SacramentRecord;
use App\Models\Scandal;
use App\Models\SpiritualActRecord;
use App\Models\Temptation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasSpiritualState
{
    public function spiritualState(): HasOne
    {
        return $this->hasOne(CharacterSpiritualState::class)->where('is_current', true);
    }

    public function canonicalState(): HasOne
    {
        return $this->hasOne(CharacterCanonicalState::class)->where('is_current', true);
    }

    public function sacramentRecords(): HasMany
    {
        return $this->hasMany(SacramentRecord::class, 'subject_character_id');
    }

    public function penances(): HasMany
    {
        return $this->hasMany(Penance::class);
    }

    public function scandals(): HasMany
    {
        return $this->hasMany(Scandal::class);
    }

    public function temptations(): HasMany
    {
        return $this->hasMany(Temptation::class);
    }

    public function demonicInfluences(): HasMany
    {
        return $this->hasMany(DemonicInfluence::class);
    }

    public function spiritualActs(): HasMany
    {
        return $this->hasMany(SpiritualActRecord::class);
    }
}
