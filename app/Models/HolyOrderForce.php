<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolyOrderForce extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quality_modifier' => 'float',
        'modifier_breakdown' => 'array',
        'raised_date' => 'date',
        'disbanded_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class, 'holy_order_id');
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(HolyOrderHouse::class, 'house_id');
    }

    public function commander(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'commander_character_id');
    }

    public function stationedTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'stationed_territory_id');
    }

    public function totalMen(): int
    {
        return (int) $this->knights + (int) $this->sergeants + (int) $this->chaplains;
    }
}
