<?php

namespace App\Domain\Playable;

use App\Models\Character;
use App\Models\User;
use App\Models\World;
use RuntimeException;

final class CurrentPlay
{
    public function __construct(
        public User $user,
        public World $world,
        public Character $ruler
    ) {
    }

    public static function require(User $user): self
    {
        $user->loadMissing('character');
        if (!$user->character || !$user->world_id) {
            throw new RuntimeException('This account does not control a living character.');
        }

        return new self($user, World::query()->findOrFail($user->world_id), $user->character);
    }
}
