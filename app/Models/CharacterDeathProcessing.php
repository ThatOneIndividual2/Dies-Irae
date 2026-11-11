<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterDeathProcessing extends Model
{
    protected $guarded = [];

    protected $casts = [
        'processed_on' => 'date',
        'notes' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function heir(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'heir_character_id');
    }
}
