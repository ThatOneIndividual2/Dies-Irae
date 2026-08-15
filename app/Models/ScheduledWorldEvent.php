<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledWorldEvent extends Model
{
    protected $fillable = [
        'world_id',
        'process_at',
        'event_type',
        'status',
        'attempts',
        'locked_at',
        'locked_by',
        'payload',
        'processed_at',
        'failed_at',
        'failure_message',
        'idempotency_key',
    ];

    protected $casts = [
        'process_at' => 'date',
        'payload' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'locked_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
