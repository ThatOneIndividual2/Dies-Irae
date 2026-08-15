<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NamedDemon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'return_authorized' => 'boolean',
        'destroyed_on' => 'date',
        'return_min_apocalypse' => 'integer',
        'epithets' => 'array',
        'titles' => 'array',
        'themes' => 'array',
        'channels' => 'array',
        'hidden_true_state' => 'array',
        'vulnerabilities' => 'array',
        'known_manifestations' => 'array',
        'physical_manifest_forbidden_phases' => 'array',
        'physical_manifest_min_apocalypse' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'host_character_id');
    }

    public function faction(): BelongsTo
    {
        return $this->belongsTo(DemonicFaction::class, 'faction_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(NamedDemonStatusHistory::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(NamedDemonObjective::class);
    }

    public function servants(): HasMany
    {
        return $this->hasMany(NamedDemonServant::class);
    }

    public function rivalries(): HasMany
    {
        return $this->hasMany(NamedDemonRivalry::class);
    }

    public function influence(): HasMany
    {
        return $this->hasMany(NamedDemonTerritoryInfluence::class);
    }

    public function acts(): HasMany
    {
        return $this->hasMany(NamedDemonAct::class);
    }

    public function grudges(): HasMany
    {
        return $this->hasMany(NamedDemonGrudge::class);
    }

    public function knowledge(): HasMany
    {
        return $this->hasMany(NamedDemonKnowledge::class);
    }
}
