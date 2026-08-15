<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PapalObedience extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pledged_date' => 'date',
        'ended_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function papacy(): BelongsTo
    {
        return $this->belongsTo(Papacy::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class, 'papal_claim_id');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'claimant_character_id');
    }
}
