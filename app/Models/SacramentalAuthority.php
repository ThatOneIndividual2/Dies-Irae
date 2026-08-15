<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentalAuthority extends Model
{
    protected $guarded = [];

    protected $casts = [
        'granted_date' => 'date',
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

    public function authorizingOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'authorizing_office_id');
    }
}
