<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemonicFaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'themes' => 'array',
        'rivals' => 'array',
    ];

    public function influences() { return $this->hasMany(DemonicInfluence::class, 'faction_id'); }
    public function cults() { return $this->hasMany(Cult::class, 'faction_id'); }
    public function namedDemons() { return $this->hasMany(NamedDemon::class, 'faction_id'); }

}
