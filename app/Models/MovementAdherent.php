<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementAdherent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'joined_date' => 'date',
        'left_date' => 'date',
        'is_current' => 'boolean',
        'is_clergy' => 'boolean',
        'is_patron' => 'boolean',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
