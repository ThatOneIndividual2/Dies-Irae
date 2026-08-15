<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterPersonality extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ambitious' => 'integer',
        'pious' => 'integer',
        'cautious' => 'integer',
        'vengeful' => 'integer',
        'greedy' => 'integer',
        'loyal' => 'integer',
        'zealous' => 'integer',
        'compassionate' => 'integer',
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
