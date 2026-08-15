<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'revealed' => 'boolean',
        'destroyed' => 'boolean',
        'strength' => 'integer',
        'activity' => 'integer',
    ];

    public function faction() { return $this->belongsTo(DemonicFaction::class, 'faction_id'); }
    public function territory() { return $this->belongsTo(Territory::class); }
    public function organization() { return $this->belongsTo(CultOrganization::class, 'organization_id'); }

}
