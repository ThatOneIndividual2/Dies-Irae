<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterSpiritualState extends Model
{
    use HasFactory;

    protected $fillable = [
        'world_id', 'character_id',
        'faith', 'hope', 'charity',
        'pride', 'greed', 'lust', 'envy', 'gluttony', 'wrath', 'sloth',
        'despair', 'repentance', 'personal_corruption',
        'is_current',
    ];

    protected $casts = [
        'faith' => 'integer',
        'hope' => 'integer',
        'charity' => 'integer',
        'pride' => 'integer',
        'greed' => 'integer',
        'lust' => 'integer',
        'envy' => 'integer',
        'gluttony' => 'integer',
        'wrath' => 'integer',
        'sloth' => 'integer',
        'despair' => 'integer',
        'personal_corruption' => 'integer',
        'is_current' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(SpiritualDispositionLedger::class, 'character_id', 'character_id');
    }

    public function clampAndFill(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!in_array($key, $this->fillable, true) || $key === 'world_id' || $key === 'character_id' || $key === 'is_current' || $key === 'repentance') {
                continue;
            }
            $this->{$key} = max(0, min(100, (int) $value));
        }
    }
}
