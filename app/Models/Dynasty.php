<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dynasty extends Model
{
    protected $guarded = [];

    protected $casts = [
        'founded_date' => 'date',
        'extinct_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function houses(): HasMany
    {
        return $this->hasMany(DynastyHouse::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }
}
