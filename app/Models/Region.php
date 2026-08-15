<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territories(): HasMany
    {
        return $this->hasMany(Territory::class);
    }
}
