<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamedDemonAct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acted_on' => 'date',
        'payload' => 'array',
    ];

    public function demon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class, 'named_demon_id');
    }
}
