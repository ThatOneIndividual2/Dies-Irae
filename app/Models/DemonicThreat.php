<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DemonicThreat extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'world_id',
        'demonic_faction_id',
        'territory_id',
        'key',
        'name',
        'status',
        'intensity',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

public function faction(): BelongsTo
    {
        return $this->belongsTo(DemonicFaction::class, 'demonic_faction_id');
    }

public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
