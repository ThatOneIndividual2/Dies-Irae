<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimageSite extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
