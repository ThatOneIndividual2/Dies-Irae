<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterControlHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_on' => 'date',
        'ended_on' => 'date',
        'is_current' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function character() { return $this->belongsTo(Character::class); }

}
