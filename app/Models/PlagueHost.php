<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlagueHost extends Model
{
    protected $fillable = [
        'world_id',
        'wave_id',
        'host_type',
        'host_key',
        'location_settlement_id',
        'heads',
        'incubating',
        'infectious',
        'recovered',
        'dead',
        'local_supernatural_bp',
        'incubation_batches',
        'infectious_batches',
    ];

    protected $casts = [
        'incubation_batches' => 'array',
        'infectious_batches' => 'array',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(PlagueWave::class, 'wave_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'location_settlement_id');
    }
}
