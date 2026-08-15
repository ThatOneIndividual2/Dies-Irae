<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamedDemonStatusHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changed_on' => 'date',
    ];

    public function namedDemon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class);
    }
}
