<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimageTraffic extends Model
{
    protected $table = 'pilgrimage_traffic';

    protected $guarded = [];

    protected $casts = [
        'pilgrims_count' => 'integer',
        'income_delta' => 'integer',
        'prestige_delta' => 'integer',
        'cult_delta' => 'integer',
        'recorded_date' => 'date',
        'metadata' => 'array',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(PilgrimageRoute::class, 'pilgrimage_route_id');
    }
}
