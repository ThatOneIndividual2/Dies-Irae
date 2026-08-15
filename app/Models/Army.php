<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Army extends Model
{
    protected $guarded = [];

    protected $casts = [
        'strength' => 'integer',
        'is_active' => 'boolean',
    ];

    public function world() { return $this->belongsTo(World::class); }
    public function commander() { return $this->belongsTo(Character::class, 'commander_character_id'); }
    public function owner() { return $this->belongsTo(Character::class, 'owner_character_id'); }
    public function territory() { return $this->belongsTo(Territory::class); }

}
