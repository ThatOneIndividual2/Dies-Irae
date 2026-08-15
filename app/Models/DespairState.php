<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DespairState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'recorded_on' => 'date',
    ];

    public function territory() { return $this->belongsTo(Territory::class); }

}
