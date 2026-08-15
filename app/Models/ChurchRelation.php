<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChurchRelation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'standing' => 'integer',
    ];

    public function character() { return $this->belongsTo(Character::class); }
    public function see() { return $this->belongsTo(See::class); }

}
