<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCareerEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'occurred_date' => 'date',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
