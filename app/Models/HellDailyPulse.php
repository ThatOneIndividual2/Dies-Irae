<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HellDailyPulse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pulsed_on' => 'date',
        'notes' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
