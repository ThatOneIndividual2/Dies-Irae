<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VassalRelationship extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }

    public function liege(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'liege_character_id');
    }

    public function vassal(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'vassal_character_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(FeudalContract::class, 'contract_id');
    }
}
