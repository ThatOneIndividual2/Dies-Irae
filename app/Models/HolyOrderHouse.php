<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolyOrderHouse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'is_active' => 'boolean',
        'founded_date' => 'date',
        'suppressed_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function commander(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'commander_character_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HolyOrderMembership::class, 'house_id');
    }
}
