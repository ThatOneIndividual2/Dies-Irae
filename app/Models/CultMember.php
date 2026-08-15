<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultMember extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recruited_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CultOrganization::class, 'organization_id');
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(CultCell::class, 'cell_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
