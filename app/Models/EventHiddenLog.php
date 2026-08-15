<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHiddenLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'result' => 'array',
        'applied_on' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
