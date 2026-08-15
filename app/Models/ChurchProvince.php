<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChurchProvince extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function metropolitanSee(): BelongsTo
    {
        return $this->belongsTo(See::class, 'metropolitan_see_id');
    }

    public function sees(): HasMany
    {
        return $this->hasMany(See::class);
    }

    public function metropolitanOffice(): HasOne
    {
        return $this->hasOne(SpiritualOffice::class);
    }
}
