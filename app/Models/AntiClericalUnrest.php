<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntiClericalUnrest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'started_date' => 'date',
        'ended_date' => 'date',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
