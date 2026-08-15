<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiracleWitness extends Model
{
    protected $guarded = [];

    public function miracle(): BelongsTo
    {
        return $this->belongsTo(Miracle::class);
    }
}
