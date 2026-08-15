<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCareer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
