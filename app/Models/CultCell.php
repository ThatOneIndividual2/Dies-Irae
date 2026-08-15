<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CultCell extends Model
{
    protected $guarded = [];

    protected $casts = [
        'strength' => 'integer',
        'secrecy' => 'integer',
        'is_current' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CultOrganization::class, 'organization_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CultMember::class, 'cell_id');
    }
}
