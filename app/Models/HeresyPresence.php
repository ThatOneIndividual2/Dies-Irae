<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeresyPresence extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function heresy(): BelongsTo
    {
        return $this->belongsTo(Heresy::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function getIsPublicAttribute(): bool
    {
        return ($this->attributes['status'] ?? null) === 'public';
    }

    public function getIntensityAttribute(): int
    {
        return (int) ($this->attributes['intensity'] ?? 1);
    }
}
