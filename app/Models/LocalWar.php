<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalWar extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function theater(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'theater_territory_id');
    }
}
