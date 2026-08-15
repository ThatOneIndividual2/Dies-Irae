<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Realm extends Model
{
    protected $guarded = [];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function topLiege(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'top_liege_character_id');
    }

    public function primaryTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'primary_title_id');
    }

    public function vassalRelationships(): HasMany
    {
        return $this->hasMany(VassalRelationship::class);
    }
}
