<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApocalypseSignal extends Model
{
    protected $fillable = [
        'world_id',
        'signal_key',
        'polarity',
        'magnitude',
        'world_date',
        'source_type',
        'source_id',
        'territory_id',
        'applied_deltas',
        'payload',
        'idempotency_key',
    ];

    protected $casts = [
        'world_date' => 'date',
        'applied_deltas' => 'array',
        'payload' => 'array',
        'magnitude' => 'integer',
        'source_id' => 'integer',
        'territory_id' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
