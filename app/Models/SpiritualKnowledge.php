<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpiritualKnowledge extends Model
{
    use HasFactory;

    protected $table = 'spiritual_knowledge';

    protected $fillable = [
        'world_id', 'observer_character_id', 'subject_character_id', 'facet',
        'certainty', 'is_sealed', 'source_type', 'source_id', 'acquired_date', 'metadata',
    ];

    protected $casts = [
        'is_sealed' => 'boolean',
        'acquired_date' => 'date',
        'metadata' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'observer_character_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'subject_character_id');
    }
}
