<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PilgrimageRoute extends Model
{
    protected $guarded = [];

    protected $casts = [
        'traffic_intensity' => 'integer',
        'is_active' => 'boolean',
        'prestige_yield' => 'integer',
        'income_yield' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(PilgrimageStop::class)->orderBy('sequence');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Pilgrimage::class);
    }
}
