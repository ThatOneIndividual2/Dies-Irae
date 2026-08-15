<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FalseProphetState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'following' => 'integer',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function prophet(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'prophet_character_id');
    }
}
