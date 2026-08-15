<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCanonicalState extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id',
        'is_baptized', 'baptism_date', 'is_confirmed', 'confirmation_date',
        'eucharist_standing', 'holy_orders_grade',
        'matrimonial_bond_character_id', 'matrimony_date',
        'last_anointing_date', 'censure', 'censure_source_type', 'censure_source_id',
        'grave_unconfessed', 'is_current',
    ];

    protected $casts = [
        'is_baptized' => 'boolean',
        'is_confirmed' => 'boolean',
        'grave_unconfessed' => 'boolean',
        'is_current' => 'boolean',
        'baptism_date' => 'date',
        'confirmation_date' => 'date',
        'matrimony_date' => 'date',
        'last_anointing_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function bondSpouse(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'matrimonial_bond_character_id');
    }
}
