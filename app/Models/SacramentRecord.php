<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'sacrament_type', 'subject_character_id', 'minister_character_id',
        'spouse_character_id', 'place_type', 'place_id', 'occurred_date',
        'validity', 'reasons', 'orders_grade', 'consequences_applied', 'metadata',
    ];

    protected $casts = [
        'occurred_date' => 'date',
        'reasons' => 'array',
        'metadata' => 'array',
        'consequences_applied' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'subject_character_id');
    }

    public function minister(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'minister_character_id');
    }

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'spouse_character_id');
    }
}
