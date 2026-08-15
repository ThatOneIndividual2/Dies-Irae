<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopularMovementState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fervor' => 'integer',
        'violence' => 'integer',
        'church_regularized' => 'boolean',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }
}
