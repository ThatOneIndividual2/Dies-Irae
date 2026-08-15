<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderVow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(HolyOrderMembership::class, 'membership_id');
    }
}
