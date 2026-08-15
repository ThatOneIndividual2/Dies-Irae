<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaintFeast extends Model
{
    protected $guarded = [];

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }
}
