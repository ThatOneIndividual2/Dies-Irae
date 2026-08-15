<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultObjective extends Model
{
    protected $guarded = [];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CultOrganization::class, 'organization_id');
    }
}
