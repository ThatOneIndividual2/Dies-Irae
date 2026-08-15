<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'moved_on' => 'date',
    ];
}
