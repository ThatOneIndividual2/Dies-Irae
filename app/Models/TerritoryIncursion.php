<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritoryIncursion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'settlement_corrupted' => 'boolean',
        'changed_on' => 'date',
        'corruption' => 'integer',
        'local_manifestation' => 'integer',
        'cult_activity' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function strongholdFaction(): BelongsTo
    {
        return $this->belongsTo(DemonicFaction::class, 'stronghold_faction_id');
    }
}
