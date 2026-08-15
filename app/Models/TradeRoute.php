<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeRoute extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'volume' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function fromTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'from_territory_id');
    }

    public function toTerritory(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'to_territory_id');
    }
}
