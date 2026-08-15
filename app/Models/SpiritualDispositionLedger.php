<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpiritualDispositionLedger extends Model
{
    use HasFactory;

    protected $table = 'spiritual_disposition_ledgers';

    protected $fillable = [
        'world_id', 'character_id', 'facet', 'delta', 'reason',
        'source_type', 'source_id', 'occurred_date', 'metadata',
    ];

    protected $casts = [
        'delta' => 'integer',
        'occurred_date' => 'date',
        'metadata' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
