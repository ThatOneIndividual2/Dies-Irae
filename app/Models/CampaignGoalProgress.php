<?php

namespace App\Models;

use App\Domain\Campaign\CampaignPack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignGoalProgress extends Model
{
    protected $guarded = [];

    protected $casts = [
        'notes' => 'array',
        'progress' => 'integer',
    ];

    /** @var array<string, array>|null */
    private static ?array $goalDefinitions = null;

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function displayTitle(): string
    {
        $def = $this->definition();
        if (is_array($def) && isset($def['name']) && $def['name'] !== '') {
            return (string) $def['name'];
        }

        return str_replace('_', ' ', (string) $this->goal_key);
    }

    public function displayDescription(): ?string
    {
        $def = $this->definition();
        $description = is_array($def) ? ($def['description'] ?? null) : null;
        if ($description === null || $description === '') {
            return null;
        }

        return (string) $description;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'available' => 'available',
            'active' => 'in progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'locked' => 'locked',
            default => str_replace('_', ' ', (string) $this->status),
        };
    }

    public function progressLabel(): string
    {
        return ((int) $this->progress).'%';
    }

    /**
     * @return array{key?: string, name?: string, description?: string}|null
     */
    public function definition(): ?array
    {
        $all = self::goalDefinitions();
        $key = (string) $this->goal_key;

        return $all[$key] ?? null;
    }

    /**
     * @return array<string, array{key?: string, name?: string, description?: string}>
     */
    private static function goalDefinitions(): array
    {
        if (self::$goalDefinitions !== null) {
            return self::$goalDefinitions;
        }

        $pack = CampaignPack::europa1347()->get('goals');
        $indexed = [];
        foreach ($pack['goals'] ?? [] as $goal) {
            if (!is_array($goal) || !isset($goal['key'])) {
                continue;
            }
            $indexed[(string) $goal['key']] = $goal;
        }

        return self::$goalDefinitions = $indexed;
    }
}
