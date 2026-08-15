<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApocalypseMilestoneRecord extends Model
{
    protected $fillable = [
        'world_id',
        'milestone_key',
        'name',
        'reached_on',
        'phase_key',
        'apply_floors',
        'apply_ceilings',
        'meters_at_reach',
        'irreversible',
    ];

    protected $casts = [
        'reached_on' => 'date',
        'apply_floors' => 'array',
        'apply_ceilings' => 'array',
        'meters_at_reach' => 'array',
        'irreversible' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
