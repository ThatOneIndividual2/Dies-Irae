<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pilgrimage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'completed_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(PilgrimageRoute::class, 'pilgrimage_route_id');
    }
}
