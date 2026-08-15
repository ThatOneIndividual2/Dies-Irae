<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Heresy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'condemned_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function condemnedByOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'condemned_by_office_id');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(HeresyPresence::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }
}
