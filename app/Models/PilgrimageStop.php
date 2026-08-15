<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimageStop extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sequence' => 'integer',
        'lodging_pressure' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(PilgrimageRoute::class, 'pilgrimage_route_id');
    }
}
