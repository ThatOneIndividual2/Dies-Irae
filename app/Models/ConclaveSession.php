<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConclaveSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'seat_relocated' => 'boolean',
        'extraordinary_rules' => 'array',
        'opened_date' => 'date',
        'enthroned_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function papacy(): BelongsTo
    {
        return $this->belongsTo(Papacy::class);
    }

    public function electors(): HasMany
    {
        return $this->hasMany(ConclaveElector::class);
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(ConclaveBallot::class);
    }
}
