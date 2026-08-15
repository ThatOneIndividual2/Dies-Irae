<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuinState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_on' => 'date',
        'is_current' => 'boolean',
    ];

}
