<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuinStateHistory extends Model
{
    protected $fillable = [
        'world_id',
        'settlement_id',
        'from_state',
        'to_state',
        'reason',
        'tick',
        'changed_on',
    ];

    protected $casts = [
        'changed_on' => 'date',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
