<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchismSeeAllegiance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pledged_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function schism(): BelongsTo
    {
        return $this->belongsTo(Schism::class);
    }

    public function see(): BelongsTo
    {
        return $this->belongsTo(See::class);
    }

    public function papalClaim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class);
    }
}
