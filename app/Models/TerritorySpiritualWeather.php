<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritorySpiritualWeather extends Model
{
    protected $table = 'territory_spiritual_weather';

    protected $fillable = [
        'world_id',
        'territory_id',
        'local_corruption',
        'local_despair',
        'local_manifestation',
        'local_sanctity',
        'is_sanctuary',
        'last_changed_on',
    ];

    protected $casts = [
        'is_sanctuary' => 'boolean',
        'last_changed_on' => 'date',
        'local_corruption' => 'integer',
        'local_despair' => 'integer',
        'local_manifestation' => 'integer',
        'local_sanctity' => 'integer',
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
