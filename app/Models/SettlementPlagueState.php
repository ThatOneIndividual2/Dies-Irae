<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementPlagueState extends Model
{
    protected $fillable = [
        'world_id',
        'settlement_id',
        'wave_id',
        'incubating',
        'infectious',
        'recovered',
        'dead',
        'local_supernatural_bp',
        'incubation_batches',
        'infectious_batches',
        'arrived_on',
        'peaked_on',
    ];

    protected $casts = [
        'incubation_batches' => 'array',
        'infectious_batches' => 'array',
        'arrived_on' => 'date',
        'peaked_on' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function wave(): BelongsTo
    {
        return $this->belongsTo(PlagueWave::class, 'wave_id');
    }
}
