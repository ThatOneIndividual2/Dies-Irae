<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRelationship extends Model
{
    protected $guarded = [];

    protected $casts = [
        'standing' => 'integer',
    ];
}
