<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaintShrine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pilgrimage_value' => 'integer',
    ];

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }
}
