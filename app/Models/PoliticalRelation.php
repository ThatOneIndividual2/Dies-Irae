<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticalRelation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function fromRealm(): BelongsTo
    {
        return $this->belongsTo(Realm::class, 'from_realm_id');
    }

    public function toRealm(): BelongsTo
    {
        return $this->belongsTo(Realm::class, 'to_realm_id');
    }
}
