<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelicCustody extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acquired_date' => 'date',
        'lost_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function relic(): BelongsTo
    {
        return $this->belongsTo(Relic::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function holyOrder(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class);
    }

    public function holyOrderHouse(): BelongsTo
    {
        return $this->belongsTo(HolyOrderHouse::class, 'holy_order_house_id');
    }
}
