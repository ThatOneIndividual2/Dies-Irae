<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelicProvenance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function relic(): BelongsTo
    {
        return $this->belongsTo(Relic::class);
    }
}
