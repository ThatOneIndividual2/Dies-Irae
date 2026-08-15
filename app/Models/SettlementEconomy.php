<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementEconomy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'monastery_overwhelmed' => 'boolean',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
