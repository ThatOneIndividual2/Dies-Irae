<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolyOrderMembership extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recruited_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(HolyOrderHouse::class, 'house_id');
    }

    public function vows(): HasMany
    {
        return $this->hasMany(HolyOrderVow::class, 'membership_id');
    }

    public function currentVows(): HasMany
    {
        return $this->hasMany(HolyOrderVow::class, 'membership_id')->where('is_current', true);
    }
}
