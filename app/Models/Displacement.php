<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Displacement extends Model
{
    protected $fillable = [
        'world_id',
        'flow_key',
        'from_settlement_id',
        'to_settlement_id',
        'cause',
        'nobles',
        'clergy',
        'burghers',
        'peasants',
        'unfree',
        'carrying_infectious',
        'carrying_incubating',
        'status',
        'departed_tick',
        'arrived_tick',
        'departed_on',
        'arrived_on',
    ];

    protected $casts = [
        'departed_on' => 'date',
        'arrived_on' => 'date',
    ];

    public function fromSettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'from_settlement_id');
    }

    public function toSettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'to_settlement_id');
    }

    public function souls(): int
    {
        return (int) $this->nobles + (int) $this->clergy + (int) $this->burghers
            + (int) $this->peasants + (int) $this->unfree;
    }
}
