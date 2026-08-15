<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamedDemonKnowledge extends Model
{
    protected $table = 'named_demon_knowledge';

    protected $guarded = [];

    protected $casts = ['learned_on' => 'date'];

    public function demon(): BelongsTo
    {
        return $this->belongsTo(NamedDemon::class, 'named_demon_id');
    }
}
