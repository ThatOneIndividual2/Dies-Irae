<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlagueWave extends Model
{
    protected $fillable = [
        'world_id',
        'key',
        'strain_key',
        'name',
        'status',
        'started_date',
        'ended_date',
        'started_on',
        'infectiousness',
        'mortality',
        'incubation_ticks',
        'infectious_ticks',
        'supernatural_amplification',
        'supernatural',
        'origin_settlement_id',
        'peaked_on',
        'strain',
    ];

    protected $casts = [
        'supernatural' => 'boolean',
        'started_date' => 'date',
        'ended_date' => 'date',
        'peaked_on' => 'date',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'origin_settlement_id');
    }

    public function settlementStates(): HasMany
    {
        return $this->hasMany(SettlementPlagueState::class, 'wave_id');
    }

    public function territoryStates(): HasMany
    {
        return $this->hasMany(TerritoryPlagueState::class, 'plague_wave_id');
    }

    public function states(): HasMany
    {
        return $this->territoryStates();
    }

    public function getStartedOnAttribute()
    {
        return $this->started_date;
    }

    public function getIsActiveAttribute(): bool
    {
        return ($this->attributes['status'] ?? null) === 'active';
    }
}
