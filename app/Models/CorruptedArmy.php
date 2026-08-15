<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorruptedArmy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cleansed' => 'boolean',
        'corruption' => 'integer',
        'strength' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function originalRealm(): BelongsTo
    {
        return $this->belongsTo(Realm::class, 'original_realm_id');
    }

    public function originalCommander(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'original_commander_character_id');
    }
}
