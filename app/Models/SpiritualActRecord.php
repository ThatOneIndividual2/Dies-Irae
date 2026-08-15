<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpiritualActRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id', 'act_type', 'occurred_date',
        'is_public', 'source_type', 'source_id', 'deltas', 'metadata',
    ];

    protected $casts = [
        'occurred_date' => 'date',
        'is_public' => 'boolean',
        'deltas' => 'array',
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
