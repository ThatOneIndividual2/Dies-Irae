<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpiritualOffice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_papal_apex' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function see(): BelongsTo
    {
        return $this->belongsTo(See::class);
    }

    public function monastery(): BelongsTo
    {
        return $this->belongsTo(Monastery::class);
    }

    public function religiousOrder(): BelongsTo
    {
        return $this->belongsTo(ReligiousOrder::class);
    }

    public function holyOrder(): BelongsTo
    {
        return $this->belongsTo(HolyOrder::class);
    }

    public function holderships(): HasMany
    {
        return $this->hasMany(SpiritualOfficeHoldership::class);
    }

    public function currentHoldership(): HasOne
    {
        return $this->hasOne(SpiritualOfficeHoldership::class)->where('is_current', true);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ClergyAppointment::class);
    }
}
