<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Monastery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_exempt' => 'boolean',
        'is_active' => 'boolean',
        'religious_population' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function religiousOrder(): BelongsTo
    {
        return $this->belongsTo(ReligiousOrder::class);
    }

    public function abbotOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'abbot_office_id');
    }

    public function currentAbbot(): HasOne
    {
        return $this->hasOne(SpiritualOfficeHoldership::class, 'spiritual_office_id', 'abbot_office_id')
            ->where('is_current', true);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function see(): BelongsTo
    {
        return $this->belongsTo(See::class);
    }
}
