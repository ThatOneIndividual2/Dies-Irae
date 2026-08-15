<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClergyAppointment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'appointed_date' => 'date',
        'resolved_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'spiritual_office_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function appointedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'appointed_by_character_id');
    }

    public function investedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'invested_by_character_id');
    }

    public function competingAppointment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'competing_appointment_id');
    }
}
