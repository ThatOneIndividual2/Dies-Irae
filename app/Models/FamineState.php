<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamineState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'as_of_date' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
