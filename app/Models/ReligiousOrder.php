<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReligiousOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sanctioned_date' => 'date',
        'suppressed_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function monasteries(): HasMany
    {
        return $this->hasMany(Monastery::class);
    }

    public function holyOrders(): HasMany
    {
        return $this->hasMany(HolyOrder::class);
    }
}
