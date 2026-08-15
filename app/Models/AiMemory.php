<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMemory extends Model
{
    protected $table = 'ai_memories';

    protected $guarded = [];

    protected $casts = [
        'salience' => 'integer',
        'recorded_on' => 'date',
    ];
}
