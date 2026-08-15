<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardinalProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'relationships' => 'array',
        'preferences' => 'array',
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

    public function office(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'spiritual_office_id');
    }
}
