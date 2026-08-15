<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Saint extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recognized_date' => 'date',
        'died_date' => 'date',
        'reputation' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function recognizedByOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'recognized_by_office_id');
    }

    public function relics(): HasMany
    {
        return $this->hasMany(Relic::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(SaintEvidence::class);
    }

    public function patronages(): HasMany
    {
        return $this->hasMany(SaintPatronage::class);
    }

    public function feasts(): HasMany
    {
        return $this->hasMany(SaintFeast::class);
    }

    public function shrines(): HasMany
    {
        return $this->hasMany(SaintShrine::class);
    }

    public function cults(): HasMany
    {
        return $this->hasMany(SaintCult::class)->where('is_current', true);
    }
}
