<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MassMortalityEvent extends Model
{
    protected $fillable = [
        'world_id',
        'settlement_id',
        'wave_id',
        'cause',
        'souls_lost',
        'deaths_by_class',
        'workforce_before',
        'workforce_after',
        'tax_base_before',
        'tax_base_after',
        'levy_base_before',
        'levy_base_after',
        'clergy_vacancies',
        'succession_pressure',
        'noble_extinction',
        'viability_lost',
        'rebellion_pressure',
        'migration_pressure',
        'unburied_corpses',
        'despair',
        'corruption',
        'ruin_state',
        'occurred_on',
    ];

    protected $casts = [
        'deaths_by_class' => 'array',
        'noble_extinction' => 'boolean',
        'viability_lost' => 'boolean',
        'occurred_on' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
