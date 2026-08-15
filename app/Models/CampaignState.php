<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_until' => 'date',
        'last_pulsed_on' => 'date',
        'flags' => 'array',
        'fired_families' => 'array',
        'cooldowns' => 'array',
        'revealed_mechanics' => 'array',
        'pulse_count' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'player_character_id');
    }

    public function flag(string $key, $default = false)
    {
        return ($this->flags ?? [])[$key] ?? $default;
    }

    public function setFlag(string $key, $value): void
    {
        $flags = $this->flags ?? [];
        $flags[$key] = $value;
        $this->flags = $flags;
    }
}
