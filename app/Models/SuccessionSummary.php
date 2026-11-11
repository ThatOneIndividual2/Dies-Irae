<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessionSummary extends Model
{
    protected $guarded = [];

    protected $casts = [
        'processed_on' => 'date',
    ];

    public function deceased(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'deceased_character_id');
    }

    public function heir(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'heir_character_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
