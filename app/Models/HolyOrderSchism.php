<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderSchism extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }
}
