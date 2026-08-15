<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderPatronage extends Model
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

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'patron_character_id');
    }

    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class, 'patron_realm_id');
    }
}
