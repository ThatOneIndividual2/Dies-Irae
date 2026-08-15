<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorruptionState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'recorded_on' => 'date',
        'started_date' => 'date',
        'is_current' => 'boolean',
        'visible_signs' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CorruptionEvent::class);
    }
}
