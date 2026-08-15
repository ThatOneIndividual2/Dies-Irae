<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHook extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'set_on' => 'date',
        'expires_on' => 'date',
        'intensity' => 'integer',
        'scope_id' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function isActiveOn(string $date): bool
    {
        if ($this->expires_on === null) {
            return true;
        }

        return $this->expires_on->toDateString() >= $date;
    }
}
