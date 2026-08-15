<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Papacy extends Model
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

    public function papalSee(): BelongsTo
    {
        return $this->belongsTo(See::class, 'papal_see_id');
    }

    public function papalOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'papal_office_id');
    }

    public function recognizedClaim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class, 'recognized_claim_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PapalClaim::class);
    }

    public function conclaves(): HasMany
    {
        return $this->hasMany(ConclaveSession::class);
    }

    public function obediences(): HasMany
    {
        return $this->hasMany(PapalObedience::class);
    }

    public function legitimacyStates(): HasMany
    {
        return $this->hasMany(ChurchLegitimacyState::class);
    }

    public function getSeatNameAttribute(): ?string
    {
        return $this->papalSee?->name;
    }
}
