<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Settlement extends Model
{
    protected $fillable = [
        'world_id',
        'territory_id',
        'holding_id',
        'settlement_key',
        'name',
        'kind',
        'ruin_state',
        'ruin_reason',
        'ticks_abandoned',
        'hell_occupation',
    ];

    protected $casts = [
        'hell_occupation' => 'boolean',
        'ticks_abandoned' => 'integer',
    ];

    public function population(): HasOne
    {
        return $this->hasOne(SettlementPopulation::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
