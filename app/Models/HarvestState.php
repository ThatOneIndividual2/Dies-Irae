<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recorded_on' => 'date',
        'yield_index' => 'integer',
        'harvest_year' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
