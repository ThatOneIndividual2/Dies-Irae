<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcommunicationState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issued_date' => 'date',
        'lifted_date' => 'date',
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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'issued_by_character_id');
    }

    public function issuingOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'issuing_office_id');
    }
}
