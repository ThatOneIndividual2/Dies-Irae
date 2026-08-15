<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClergyStatus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function religiousOrder(): BelongsTo
    {
        return $this->belongsTo(ReligiousOrder::class);
    }

    public function monastery(): BelongsTo
    {
        return $this->belongsTo(Monastery::class);
    }
}
