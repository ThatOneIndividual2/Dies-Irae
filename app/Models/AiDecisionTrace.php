<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDecisionTrace extends Model
{
    protected $guarded = [];

    protected $casts = [
        'trace' => 'array',
        'score' => 'float',
        'decided_on' => 'date',
    ];
}
