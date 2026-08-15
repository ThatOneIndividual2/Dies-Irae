<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerritoryAdjacency extends Model
{
    protected $guarded = [];

    public function fromTerritory() { return $this->belongsTo(Territory::class, 'from_territory_id'); }
    public function toTerritory() { return $this->belongsTo(Territory::class, 'to_territory_id'); }

}
