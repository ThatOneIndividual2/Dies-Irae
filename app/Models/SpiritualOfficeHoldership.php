<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpiritualOfficeHoldership extends Model
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

    public function office(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'spiritual_office_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'holder_character_id');
    }

    public function appointedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'appointed_by_character_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(ClergyAppointment::class, 'appointment_id');
    }
}
