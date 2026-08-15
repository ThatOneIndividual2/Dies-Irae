<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaintCult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'is_current' => 'boolean',
    ];

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }
}
