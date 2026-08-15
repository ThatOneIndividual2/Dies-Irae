<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class See extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_exempt' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function churchProvince(): BelongsTo
    {
        return $this->belongsTo(ChurchProvince::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'territory_id');
    }

    public function parentSee(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_see_id');
    }

    public function childSees(): HasMany
    {
        return $this->hasMany(self::class, 'parent_see_id');
    }

    public function ordinaryOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'ordinary_office_id');
    }

    public function offices(): HasMany
    {
        return $this->hasMany(SpiritualOffice::class);
    }

    public function jurisdictions(): HasMany
    {
        return $this->hasMany(SeeTerritory::class);
    }

    public function investitureRights(): HasMany
    {
        return $this->hasMany(InvestitureRight::class);
    }

    public function currentInvestitureRight(): HasOne
    {
        return $this->hasOne(InvestitureRight::class)->where('is_current', true);
    }
}
