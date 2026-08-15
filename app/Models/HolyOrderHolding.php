<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderHolding extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acquired_date' => 'date',
        'confiscated_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'donor_character_id');
    }
}
