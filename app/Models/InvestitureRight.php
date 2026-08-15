<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestitureRight extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function see(): BelongsTo
    {
        return $this->belongsTo(See::class);
    }

    public function nominator(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'nominator_character_id');
    }

    public function nominatorTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'nominator_title_id');
    }
}
