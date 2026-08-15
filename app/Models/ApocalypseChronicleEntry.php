<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApocalypseChronicleEntry extends Model
{
    protected $fillable = [
        'world_id',
        'world_date',
        'entry_type',
        'subject_key',
        'title',
        'body',
        'payload',
    ];

    protected $casts = [
        'world_date' => 'date',
        'payload' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
