<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Temptation extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id', 'vice', 'intensity', 'stage', 'resolution',
        'source_type', 'source_id', 'opened_date', 'resolved_date', 'is_current',
    ];

    protected $casts = [
        'intensity' => 'integer',
        'opened_date' => 'date',
        'resolved_date' => 'date',
        'is_current' => 'boolean',
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
