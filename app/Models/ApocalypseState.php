<?php

namespace App\Models;

use App\Domain\Apocalypse\ApocalypseMeters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApocalypseState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'signs' => 'array',
        'stage_entered_date' => 'date',
        'phase_entered_on' => 'date',
        'last_ticked_on' => 'date',
        'meter_floors' => 'array',
        'meter_ceilings' => 'array',
        'drift_accumulators' => 'array',
        'broken_assumptions' => 'array',
        'phase_ordinal' => 'integer',
        'pressure' => 'integer',
        'tick_count' => 'integer',
    ];

    public function getStageAttribute(): ?string
    {
        return $this->attributes['stage'] ?? $this->attributes['phase_key'] ?? null;
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function meters(): ApocalypseMeters
    {
        return ApocalypseMeters::fromState($this);
    }

    /** @param array<string, int> $meters */
    public function fillMeters(array $meters): void
    {
        foreach (ApocalypseMeters::KEYS as $key) {
            $this->{$key} = (int) ($meters[$key] ?? $this->{$key} ?? 0);
        }
    }
}
