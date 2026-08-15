<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchLegitimacyState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'as_of_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function papacy(): BelongsTo
    {
        return $this->belongsTo(Papacy::class);
    }
}
