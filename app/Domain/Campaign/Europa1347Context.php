<?php

namespace App\Domain\Campaign;

use App\Models\CampaignState;
use App\Models\Character;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Collection;

final class Europa1347Context
{
    public function __construct(
        public World $world,
        public CampaignState $campaign,
        public User $user,
        public Character $player,
        public string $archetype,
        public Collection $characters,
        public Collection $territories,
        public Collection $titles
    ) {
    }

    public function character(string $key): Character
    {
        $row = $this->characters->firstWhere('key', $key);
        if (!$row) {
            throw new \InvalidArgumentException("Unknown character {$key}");
        }

        return $row;
    }

    public function territory(string $key)
    {
        $row = $this->territories->firstWhere('key', $key);
        if (!$row) {
            throw new \InvalidArgumentException("Unknown territory {$key}");
        }

        return $row;
    }
}
