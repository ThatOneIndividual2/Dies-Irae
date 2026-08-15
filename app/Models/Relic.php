<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Relic extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'true_nature',
    ];

    protected $casts = [
        'pilgrimage_value' => 'integer',
        'destroyed_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }

    public function currentHolding(): BelongsTo
    {
        return $this->belongsTo(Holding::class, 'current_holding_id');
    }

    public function currentCustody(): HasOne
    {
        return $this->hasOne(RelicCustody::class)->where('is_current', true);
    }

    public function custodies(): HasMany
    {
        return $this->hasMany(RelicCustody::class);
    }

    public function provenances(): HasMany
    {
        return $this->hasMany(RelicProvenance::class);
    }

    public function relicEvents(): HasMany
    {
        return $this->hasMany(RelicEvent::class);
    }
}
