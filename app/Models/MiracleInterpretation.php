<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiracleInterpretation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_official' => 'boolean',
        'recorded_date' => 'date',
    ];

    public function miracle(): BelongsTo
    {
        return $this->belongsTo(Miracle::class);
    }
}
