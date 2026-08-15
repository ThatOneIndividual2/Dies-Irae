<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Battle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fought_on' => 'date',
        'notes' => 'array',
        'attacker_strength_before' => 'integer',
        'defender_strength_before' => 'integer',
        'attacker_strength_after' => 'integer',
        'defender_strength_after' => 'integer',
    ];

    public function attacker() { return $this->belongsTo(Army::class, 'attacker_army_id'); }
    public function defender() { return $this->belongsTo(Army::class, 'defender_army_id'); }
    public function territory() { return $this->belongsTo(Territory::class); }

}
