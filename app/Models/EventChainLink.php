<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventChainLink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ops' => 'array',
        'when_clause' => 'array',
        'payload' => 'array',
        'due_on' => 'date',
        'resolved_at' => 'datetime',
        'scope_id' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
