<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecularFractureResponseRecord extends Model
{
    protected $table = 'secular_fracture_responses';

    protected $guarded = [];

    protected $casts = [
        'response_date' => 'date',
        'payload' => 'array',
        'radicalization_delta' => 'integer',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'actor_character_id');
    }
}
