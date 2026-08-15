<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerritoryPlagueState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'arrived_on' => 'date',
        'peaked_on' => 'date',
        'arrived_date' => 'date',
        'peaked_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function territory() { return $this->belongsTo(Territory::class); }
    public function wave() { return $this->belongsTo(PlagueWave::class, 'plague_wave_id'); }

    public function getArrivedOnAttribute()
    {
        return $this->attributes['arrived_date'] ?? $this->attributes['arrived_on'] ?? null;
    }

    public function getPeakedOnAttribute()
    {
        return $this->attributes['peaked_date'] ?? $this->attributes['peaked_on'] ?? null;
    }
}
