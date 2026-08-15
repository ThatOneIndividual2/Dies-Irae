<?php

namespace App\Models;

use App\Models\Concerns\HasSpiritualState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    use HasSpiritualState;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'is_alive' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function dynasty(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(DynastyHouse::class, 'house_id');
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    public function currentTitleOwnerships(): HasMany
    {
        return $this->hasMany(TitleOwnership::class, 'holder_character_id')->where('is_current', true);
    }

    public function currentSpiritualHolderships(): HasMany
    {
        return $this->hasMany(SpiritualOfficeHoldership::class, 'holder_character_id')->where('is_current', true);
    }

    public function clergyStatuses(): HasMany
    {
        return $this->hasMany(ClergyStatus::class);
    }

    public function currentClergyStatus(): HasOne
    {
        return $this->hasOne(ClergyStatus::class)->where('is_current', true);
    }

    public function clergyStatus(): HasOne
    {
        return $this->hasOne(ClergyStatus::class)->where('is_current', true);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'residence_territory_id');
    }

    public function churchRelation(): HasOne
    {
        return $this->hasOne(ChurchRelation::class);
    }

    public function currentExcommunication(): HasOne
    {
        return $this->hasOne(ExcommunicationState::class)->where('is_current', true);
    }

    public function currentCareer(): HasOne
    {
        return $this->hasOne(CharacterCareer::class)->where('is_current', true);
    }

    public function careers(): HasMany
    {
        return $this->hasMany(CharacterCareer::class);
    }

    public function lifeStates(): HasMany
    {
        return $this->hasMany(CharacterLifeState::class);
    }

    public function currentLifeStates(): HasMany
    {
        return $this->hasMany(CharacterLifeState::class)->where('is_current', true);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CharacterEducation::class);
    }

    public function careerEvents(): HasMany
    {
        return $this->hasMany(CharacterCareerEvent::class);
    }

    public function heresyPresences(): HasMany
    {
        return $this->hasMany(HeresyPresence::class);
    }

    public function ownedHoldings(): HasMany
    {
        return $this->hasMany(Holding::class, 'owner_character_id');
    }

    public function displayName(): string
    {
        return $this->epithet ? $this->first_name.' '.$this->epithet : $this->first_name;
    }
}
