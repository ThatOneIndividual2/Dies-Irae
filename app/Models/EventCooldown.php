<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCooldown extends Model
{
    protected $guarded = [];

    protected $casts = [
        'available_on' => 'date',
        'last_fired_on' => 'date',
        'scope_id' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function isCooling(string $date): bool
    {
        return $this->available_on->toDateString() > $date;
    }
}
