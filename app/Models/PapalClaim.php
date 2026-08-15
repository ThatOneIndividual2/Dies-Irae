<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PapalClaim extends Model
{
    protected $guarded = [];

    protected $casts = [
        'claimed_date' => 'date',
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

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'claimant_character_id');
    }

    public function claimantOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'claimant_office_id');
    }
}
