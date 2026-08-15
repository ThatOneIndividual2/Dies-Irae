<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Territory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'population' => 'integer',
        'levy_available' => 'integer',
        'food_stores' => 'integer',
        'map_box' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'owner_character_id');
    }

    public function controller(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'controller_character_id');
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }

    public function spiritualWeather(): HasOne
    {
        return $this->hasOne(TerritorySpiritualWeather::class);
    }

    public function overlay(): HasOne
    {
        return $this->hasOne(SupernaturalOverlay::class)->where('is_current', true);
    }

    public function despair(): HasOne
    {
        return $this->hasOne(DespairState::class);
    }

    public function plagueState(): HasOne
    {
        return $this->hasOne(TerritoryPlagueState::class)->where('is_active', true);
    }

    public function cult(): HasOne
    {
        return $this->hasOne(Cult::class);
    }

    public function monastery(): HasOne
    {
        return $this->hasOne(Monastery::class);
    }

    public function armies(): HasMany
    {
        return $this->hasMany(Army::class);
    }

    public function neighbors()
    {
        return $this->belongsToMany(
            Territory::class,
            'territory_adjacencies',
            'from_territory_id',
            'to_territory_id'
        );
    }

    public function getCanonKeyAttribute(): ?string
    {
        return $this->attributes['key'] ?? $this->attributes['canon_key'] ?? null;
    }
}
