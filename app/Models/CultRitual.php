<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultRitual extends Model
{
    protected $guarded = [];

    protected $casts = [
        'performed_date' => 'date',
        'requires_sacrifice' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CultOrganization::class, 'organization_id');
    }
}
