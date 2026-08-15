<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Title extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_titular' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_title_id');
    }

    public function successionLaw(): BelongsTo
    {
        return $this->belongsTo(SuccessionLaw::class);
    }

    public function currentOwnership(): HasOne
    {
        return $this->hasOne(TitleOwnership::class)->where('is_current', true);
    }

    public function capital(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'capital_territory_id');
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(TitleOwnership::class);
    }
}
