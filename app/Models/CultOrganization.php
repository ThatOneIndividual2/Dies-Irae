<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CultOrganization extends Model
{
    protected $guarded = [];

    protected $casts = [
        'secrecy' => 'integer',
        'infiltrating_church' => 'boolean',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function faction(): BelongsTo
    {
        return $this->belongsTo(DemonicFaction::class, 'faction_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'leader_character_id');
    }

    public function cells(): HasMany
    {
        return $this->hasMany(CultCell::class, 'organization_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CultMember::class, 'organization_id');
    }

    public function rituals(): HasMany
    {
        return $this->hasMany(CultRitual::class, 'organization_id');
    }

    public function infiltrations(): HasMany
    {
        return $this->hasMany(CultInfiltration::class, 'organization_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(CultObjective::class, 'organization_id');
    }
}
