<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FractureDetection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'detected_date' => 'date',
        'public_reveal' => 'boolean',
        'sealed_confession' => 'boolean',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'reporter_character_id');
    }
}
