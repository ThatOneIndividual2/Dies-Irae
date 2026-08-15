<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HolyOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'papal_protection' => 'boolean',
        'controversial' => 'boolean',
        'sanctioned_date' => 'date',
        'suppressed_date' => 'date',
        'founded_date' => 'date',
        'dissolved_date' => 'date',
        'excommunicated_date' => 'date',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function faith(): BelongsTo
    {
        return $this->belongsTo(Faith::class);
    }

    public function religiousOrder(): BelongsTo
    {
        return $this->belongsTo(ReligiousOrder::class);
    }

    public function grandMasterOffice(): BelongsTo
    {
        return $this->belongsTo(SpiritualOffice::class, 'grand_master_office_id');
    }

    public function currentMaster(): HasOne
    {
        return $this->hasOne(SpiritualOfficeHoldership::class, 'spiritual_office_id', 'grand_master_office_id')
            ->where('is_current', true);
    }

    public function headquartersHolding(): BelongsTo
    {
        return $this->belongsTo(Holding::class, 'headquarters_holding_id');
    }

    public function houses(): HasMany
    {
        return $this->hasMany(HolyOrderHouse::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(HolyOrderMembership::class);
    }

    public function currentMemberships(): HasMany
    {
        return $this->hasMany(HolyOrderMembership::class)->where('is_current', true);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(HolyOrderHolding::class);
    }

    public function currentHoldings(): HasMany
    {
        return $this->hasMany(HolyOrderHolding::class)->where('is_current', true);
    }

    public function treasuryEntries(): HasMany
    {
        return $this->hasMany(HolyOrderTreasuryEntry::class);
    }

    public function patronages(): HasMany
    {
        return $this->hasMany(HolyOrderPatronage::class);
    }

    public function currentPatronage(): HasOne
    {
        return $this->hasOne(HolyOrderPatronage::class)->where('is_current', true);
    }

    public function recognitions(): HasMany
    {
        return $this->hasMany(HolyOrderRecognition::class);
    }

    public function currentRecognition(): HasOne
    {
        return $this->hasOne(HolyOrderRecognition::class)->where('is_current', true);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(HolyOrderMission::class);
    }

    public function forces(): HasMany
    {
        return $this->hasMany(HolyOrderForce::class);
    }

    public function schisms(): HasMany
    {
        return $this->hasMany(HolyOrderSchism::class);
    }

    public function currentSchism(): HasOne
    {
        return $this->hasOne(HolyOrderSchism::class)->where('is_current', true);
    }
}
