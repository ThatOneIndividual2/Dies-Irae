<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_on' => 'date',
        'options' => 'array',
        'effects' => 'array',
        'payload' => 'array',
        'resolved_at' => 'datetime',
        'scope_id' => 'integer',
        'actor_character_id' => 'integer',
        'parent_event_id' => 'integer',
        'weight_at_fire' => 'integer',
    ];

    public function world() { return $this->belongsTo(World::class); }
    public function isAwaiting(): bool { return $this->status === 'awaiting_decision'; }
    public function isCatalog(): bool { return ($this->engine ?? 'campaign_beat') === 'catalog'; }
    public function isVisibleToPlayer(): bool
    {
        return in_array($this->visibility ?? 'player', ['player', 'observer'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_decision' => 'awaiting decision',
            'resolved' => 'resolved',
            'scheduled' => 'scheduled',
            'cancelled' => 'cancelled',
            default => str_replace('_', ' ', (string) $this->status),
        };
    }

    public function optionLabel(?string $key = null): ?string
    {
        $key ??= $this->chosen_option;
        if ($key === null || $key === '') {
            return null;
        }

        $options = $this->options ?? [];

        return isset($options[$key]) ? (string) $options[$key] : $key;
    }

}
