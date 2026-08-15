<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultInfiltration extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'is_current' => 'boolean',
        'depth' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CultOrganization::class, 'organization_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
