<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReligiousMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'founded_date' => 'date',
        'ended_date' => 'date',
        'radicalization' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'founder_character_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'leader_character_id');
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'origin_territory_id');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(MovementPresence::class, 'movement_id');
    }

    public function adherents(): HasMany
    {
        return $this->hasMany(MovementAdherent::class, 'movement_id');
    }

    public function heresy(): HasOne
    {
        return $this->hasOne(Heresy::class, 'movement_id');
    }

    public function schism(): HasOne
    {
        return $this->hasOne(Schism::class, 'movement_id');
    }

    public function popularState(): HasOne
    {
        return $this->hasOne(PopularMovementState::class, 'movement_id');
    }

    public function cultOrganization(): HasOne
    {
        return $this->hasOne(CultOrganization::class, 'movement_id');
    }

    public function falseProphetState(): HasOne
    {
        return $this->hasOne(FalseProphetState::class, 'movement_id');
    }

    public function apostasyState(): HasOne
    {
        return $this->hasOne(ApostasyState::class, 'movement_id');
    }

    public function antiClericalUnrest(): HasOne
    {
        return $this->hasOne(AntiClericalUnrest::class, 'movement_id');
    }

    public function detections(): HasMany
    {
        return $this->hasMany(FractureDetection::class, 'movement_id');
    }

    public function spreadEvents(): HasMany
    {
        return $this->hasMany(MovementSpreadEvent::class, 'movement_id');
    }
}
