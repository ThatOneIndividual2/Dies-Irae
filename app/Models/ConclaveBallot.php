<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConclaveBallot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'majority' => 'boolean',
        'deadlocked' => 'boolean',
        'votes' => 'array',
        'tally' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConclaveSession::class, 'conclave_session_id');
    }
}
