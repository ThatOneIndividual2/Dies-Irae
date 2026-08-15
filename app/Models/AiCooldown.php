<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCooldown extends Model
{
    protected $guarded = [];

    protected $casts = [
        'available_on' => 'date',
    ];
}
