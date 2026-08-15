<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderTreasuryEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }
}
