<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelicEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_date' => 'date',
        'metadata' => 'array',
    ];

    public function relic(): BelongsTo
    {
        return $this->belongsTo(Relic::class);
    }
}
