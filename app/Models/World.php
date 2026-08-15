<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class World extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'slug',
        'name',
        'status',
        'start_date',
        'game_date',
        'current_date',
        'game_speed',
        'simulation_seed',
        'political_map_version',
        'last_processed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'game_date' => 'date',
        'current_date' => 'date',
        'last_processed_at' => 'datetime',
        'game_speed' => 'integer',
    ];

    public function getCurrentDateAttribute($value)
    {
        $raw = $value ?? ($this->attributes['current_date'] ?? $this->attributes['game_date'] ?? null);

        return $raw ? $this->asDate($raw) : null;
    }

    public function setCurrentDateAttribute($value): void
    {
        $formatted = $value ? $this->fromDateTime($value) : null;
        $this->attributes['current_date'] = $formatted;
        $this->attributes['game_date'] = $formatted;
    }

    public function getGameDateAttribute($value)
    {
        $raw = $value ?? ($this->attributes['game_date'] ?? $this->attributes['current_date'] ?? null);

        return $raw ? $this->asDate($raw) : null;
    }

    public function setGameDateAttribute($value): void
    {
        $formatted = $value ? $this->fromDateTime($value) : null;
        $this->attributes['game_date'] = $formatted;
        $this->attributes['current_date'] = $formatted;
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function dynasties()
    {
        return $this->hasMany(Dynasty::class);
    }

    public function titles()
    {
        return $this->hasMany(Title::class);
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }

    public function territories()
    {
        return $this->hasMany(Territory::class);
    }

    public function faiths()
    {
        return $this->hasMany(Faith::class);
    }

    public function papacy(): HasOne
    {
        return $this->hasOne(Papacy::class);
    }

    public function apocalypseState(): HasOne
    {
        return $this->hasOne(ApocalypseState::class);
    }

    public function apocalypseSignals(): HasMany
    {
        return $this->hasMany(ApocalypseSignal::class);
    }

    public function apocalypseMilestones(): HasMany
    {
        return $this->hasMany(ApocalypseMilestoneRecord::class);
    }

    public function apocalypseChronicle(): HasMany
    {
        return $this->hasMany(ApocalypseChronicleEntry::class);
    }

    public function scheduledEvents(): HasMany
    {
        return $this->hasMany(ScheduledWorldEvent::class);
    }

    public function campaignState(): HasOne
    {
        return $this->hasOne(CampaignState::class);
    }

    public function holyOrders(): HasMany
    {
        return $this->hasMany(HolyOrder::class);
    }
}
