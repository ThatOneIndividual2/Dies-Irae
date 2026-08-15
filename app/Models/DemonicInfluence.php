<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemonicInfluence extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'is_active' => 'boolean',
        'started_on' => 'date',
        'ended_on' => 'date',
        'metadata' => 'array',
    ];

    public function world() { return $this->belongsTo(World::class); }

    public function faction() { return $this->belongsTo(DemonicFaction::class, 'faction_id'); }
    public function character() { return $this->belongsTo(Character::class); }
    public function territory() { return $this->belongsTo(Territory::class); }

}
