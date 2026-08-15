<?php

namespace App\Domain\Campaign;

use App\Models\Army;
use App\Models\Character;
use App\Models\Dynasty;
use App\Models\Faith;
use App\Models\Monastery;
use App\Models\Papacy;
use App\Models\Realm;
use App\Models\See;
use App\Models\Territory;
use App\Models\Title;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Collection;

final class SliceContext
{
    public function __construct(
        public World $world,
        public User $user,
        public Faith $faith,
        public Dynasty $dynasty,
        public Character $ruler,
        public Character $vassal,
        public Character $enemy,
        public Character $bishop,
        public Character $abbot,
        public Character $priest,
        public Character $pope,
        public Title $countyTitle,
        public Title $baronyTitle,
        public Realm $realm,
        public See $see,
        public Monastery $monastery,
        public Papacy $papacy,
        public Collection $territories
    ) {
    }

    public function territory(string $canonKey): Territory
    {
        $territory = $this->territories->first(function (Territory $t) use ($canonKey) {
            return $t->key === $canonKey || $t->canon_key === $canonKey;
        });

        if (!$territory) {
            throw new \InvalidArgumentException("Unknown territory: {$canonKey}");
        }

        return $territory;
    }

    public function enemyArmy(): ?Army
    {
        return Army::query()
            ->where('world_id', $this->world->id)
            ->where('owner_character_id', $this->enemy->id)
            ->where('is_active', true)
            ->first();
    }

    public function demonicArmy(): ?Army
    {
        return Army::query()
            ->where('world_id', $this->world->id)
            ->where('kind', 'demonic')
            ->where('is_active', true)
            ->first();
    }
}
