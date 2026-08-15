<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorruptionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'corruption_state_id', 'event_type', 'intensity_delta',
        'occurred_date', 'source_type', 'source_id', 'metadata',
    ];

    protected $casts = [
        'intensity_delta' => 'integer',
        'occurred_date' => 'date',
        'metadata' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(CorruptionState::class, 'corruption_state_id');
    }
}
