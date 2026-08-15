<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraordinarySpiritualAuthorization extends Model
{
    protected $guarded = [];

    protected $casts = [
        'granted_date' => 'date',
        'expires_date' => 'date',
        'revoked_date' => 'date',
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

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'granted_by_character_id');
    }

    public function grantedByOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'granted_by_office_id');
    }
}
