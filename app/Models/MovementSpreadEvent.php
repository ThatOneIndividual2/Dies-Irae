<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementSpreadEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'spread_date' => 'date',
        'magnitude' => 'integer',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }
}
