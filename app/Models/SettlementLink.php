<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementLink extends Model
{
    protected $fillable = [
        'world_id',
        'from_settlement_id',
        'to_settlement_id',
        'vector',
        'intensity',
        'is_active',
        'army_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fromSettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'from_settlement_id');
    }

    public function toSettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'to_settlement_id');
    }
}
