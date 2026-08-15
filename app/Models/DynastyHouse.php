<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynastyHouse extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function dynasty(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class);
    }
}
