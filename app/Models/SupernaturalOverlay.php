<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupernaturalOverlay extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changed_on' => 'date',
        'is_current' => 'boolean',
    ];

    public function territory() { return $this->belongsTo(Territory::class); }

    public function getStateAttribute(): ?string
    {
        return $this->attributes['state'] ?? $this->attributes['kind'] ?? null;
    }
}
