<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaintEvidence extends Model
{
    protected $table = 'saint_evidences';

    protected $guarded = [];

    protected $casts = [
        'weight' => 'integer',
        'recorded_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function saint(): BelongsTo
    {
        return $this->belongsTo(Saint::class);
    }
}
