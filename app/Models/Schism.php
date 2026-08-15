<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schism extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_date' => 'date',
        'healed_date' => 'date',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ReligiousMovement::class, 'movement_id');
    }

    public function papacy(): BelongsTo
    {
        return $this->belongsTo(Papacy::class);
    }

    public function recognizedClaim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class, 'recognized_claim_id');
    }

    public function rivalClaim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class, 'rival_claim_id');
    }

    public function obediences(): HasMany
    {
        return $this->hasMany(SchismObedience::class);
    }

    public function seeAllegiances(): HasMany
    {
        return $this->hasMany(SchismSeeAllegiance::class);
    }

    public function secularBackers(): HasMany
    {
        return $this->hasMany(SchismSecularBacker::class);
    }
}
