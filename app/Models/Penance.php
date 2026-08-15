<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penance extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id', 'assigned_by_character_id', 'confession_record_id',
        'work_type', 'status', 'assigned_date', 'due_date', 'completed_date', 'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'assigned_by_character_id');
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(SacramentRecord::class, 'confession_record_id');
    }
}
