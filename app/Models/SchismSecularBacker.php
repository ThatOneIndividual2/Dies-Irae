<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchismSecularBacker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'backed_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function schism(): BelongsTo
    {
        return $this->belongsTo(Schism::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function papalClaim(): BelongsTo
    {
        return $this->belongsTo(PapalClaim::class);
    }
}
