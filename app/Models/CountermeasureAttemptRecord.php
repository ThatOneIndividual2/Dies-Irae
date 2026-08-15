<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountermeasureAttemptRecord extends Model
{
    protected $table = 'countermeasure_attempts';

    protected $guarded = [];

    protected $casts = [
        'character_died' => 'boolean',
        'breach_closed' => 'boolean',
        'factors' => 'array',
        'attempted_on' => 'date',
        'score' => 'float',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'actor_character_id');
    }
}
