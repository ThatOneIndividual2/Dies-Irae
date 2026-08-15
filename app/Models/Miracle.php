<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Miracle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_date' => 'date',
        'metadata' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }

    public function relic(): BelongsTo
    {
        return $this->belongsTo(Relic::class);
    }

    public function interpretations(): HasMany
    {
        return $this->hasMany(MiracleInterpretation::class);
    }

    public function witnesses(): HasMany
    {
        return $this->hasMany(MiracleWitness::class);
    }
}
