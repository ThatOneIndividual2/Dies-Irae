<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemonicHost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'collapsed' => 'boolean',
        'composition' => 'array',
        'strength' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function faction(): BelongsTo
    {
        return $this->belongsTo(DemonicFaction::class);
    }

    public function namedDemon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class);
    }

    public function boundBreach(): BelongsTo
    {
        return $this->belongsTo(InfernalBreach::class, 'bound_breach_id');
    }
}
