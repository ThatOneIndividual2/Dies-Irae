<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamedDemonTerritoryInfluence extends Model
{
    protected $table = 'named_demon_territory_influence';

    protected $guarded = [];

    public function demon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class, 'named_demon_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
