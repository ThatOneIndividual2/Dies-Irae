<?php

namespace App\Actions\Realms;

use App\Models\Character;
use App\Models\VassalRelationship;

final class DetermineTopLiege
{
    public function execute(Character $character, int $maxDepth = 20): Character
    {
        $current = $character;
        $seen = [];

        for ($i = 0; $i < $maxDepth; $i++) {
            if (isset($seen[$current->id])) {
                break;
            }
            $seen[$current->id] = true;

            $rel = VassalRelationship::query()
                ->where('vassal_character_id', $current->id)
                ->where('is_current', true)
                ->first();

            if (!$rel) {
                return $current;
            }

            $liege = Character::query()->find($rel->liege_character_id);
            if (!$liege) {
                return $current;
            }

            $current = $liege;
        }

        return $current;
    }
}
