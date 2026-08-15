<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeeTerritory extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function see(): BelongsTo
    {
        return $this->belongsTo(See::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
