<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Faith extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function churchProvinces(): HasMany
    {
        return $this->hasMany(ChurchProvince::class);
    }

    public function papacy(): HasOne
    {
        return $this->hasOne(Papacy::class);
    }

    public function religiousOrders(): HasMany
    {
        return $this->hasMany(ReligiousOrder::class);
    }
}
