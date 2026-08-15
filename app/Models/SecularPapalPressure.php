<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecularPapalPressure extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target_character_ids' => 'array',
        'pressure_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function papacy(): BelongsTo
    {
        return $this->belongsTo(Papacy::class);
    }

    public function ruler(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'ruler_character_id');
    }
}
