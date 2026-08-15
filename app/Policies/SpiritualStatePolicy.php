<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpiritualStatePolicy
{
    use HandlesAuthorization;

    public function viewPublic(?User $user, Character $character): bool
    {
        return true;
    }

    public function viewPrivate(User $user, Character $character): bool
    {
        return (int) $user->controlled_character_id === (int) $character->id
            || app()->environment('local', 'testing');
    }

    public function viewAsClergy(User $user, Character $character): bool
    {
        return $this->inspectAdmin($user, $character);
    }

    public function inspectAdmin(?User $user, ?Character $character = null): bool
    {
        return app()->environment('local', 'testing');
    }
}
