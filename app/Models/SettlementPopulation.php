<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementPopulation extends Model
{
    protected $fillable = [
        'world_id',
        'settlement_id',
        'nobles',
        'clergy',
        'burghers',
        'peasants',
        'unfree',
        'peak_souls',
        'morale',
        'despair',
        'corruption',
        'food_stores',
        'food_yield_per_worker',
        'unburied',
        'graveyard_capacity',
        'incubating',
        'infectious',
        'recovered',
        'quarantine',
        'corpse_handling',
        'clergy_care',
        'as_of_date',
    ];

    protected $casts = [
        'as_of_date' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function souls(): int
    {
        return (int) $this->nobles + (int) $this->clergy + (int) $this->burghers
            + (int) $this->peasants + (int) $this->unfree;
    }
}
