<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamedDemonGrudge extends Model
{
    protected $guarded = [];

    public function demon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class, 'named_demon_id');
    }
}
