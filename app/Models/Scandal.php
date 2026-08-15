<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scandal extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id', 'facet', 'severity', 'publicity',
        'source_type', 'source_id', 'broke_date', 'is_current', 'metadata',
    ];

    protected $casts = [
        'severity' => 'integer',
        'broke_date' => 'date',
        'is_current' => 'boolean',
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
