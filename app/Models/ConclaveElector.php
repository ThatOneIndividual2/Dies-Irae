<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConclaveElector extends Model
{
    protected $guarded = [];

    protected $casts = [
        'alive' => 'boolean',
        'accessible' => 'boolean',
        'present' => 'boolean',
        'is_extraordinary' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConclaveSession::class, 'conclave_session_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
