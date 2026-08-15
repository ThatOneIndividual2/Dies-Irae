<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleOwnership extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acquired_date' => 'date',
        'lost_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'holder_character_id');
    }
}
