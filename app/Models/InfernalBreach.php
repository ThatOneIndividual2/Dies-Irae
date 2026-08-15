<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfernalBreach extends Model
{
    protected $guarded = [];

    protected $casts = [
        'open' => 'boolean',
        'opened_on' => 'date',
        'closed_on' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function namedDemon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class);
    }

    public function hosts(): HasMany
    {
        return $this->hasMany(DemonicHost::class, 'bound_breach_id');
    }
}
