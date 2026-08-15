<?php

namespace App\Actions\Control;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterControlHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use RuntimeException;

final class AssignCharacterControl
{
    public function execute(User $user, Character $character, CarbonInterface $date): CharacterControlHistory
    {
        if ($user->world_id) {
            WorldBoundary::assertSameWorld((int) $user->world_id, (int) $character->world_id, 'character control');
        }

        if (!$character->is_alive) {
            throw new RuntimeException('Cannot assign control of a dead character.');
        }

        return Transactional::run(function () use ($user, $character, $date) {
            $taken = CharacterControlHistory::query()
                ->where(function ($q) use ($user, $character) {
                    $q->where(function ($q2) use ($character) {
                        $q2->where('character_id', $character->id)->where('is_current', true);
                    })->orWhere(function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('is_current', true);
                    });
                })
                ->lockForUpdate()
                ->get();

            foreach ($taken as $row) {
                if ((int) $row->character_id === (int) $character->id && (int) $row->user_id !== (int) $user->id) {
                    throw new RuntimeException('Character already has a current controller.');
                }
                if ((int) $row->user_id === (int) $user->id && (int) $row->character_id !== (int) $character->id) {
                    throw new RuntimeException('User already controls a living character.');
                }
            }

            $history = CharacterControlHistory::query()->create([
                'world_id' => $character->world_id,
                'user_id' => $user->id,
                'character_id' => $character->id,
                'started_at' => now(),
                'reason' => 'slice_seed',
                'is_current' => true,
            ]);

            $user->world_id = $character->world_id;
            $user->controlled_character_id = $character->id;
            $user->save();

            return $history;
        });
    }
}
