<?php

namespace App\Actions\Careers;

use App\Domain\Enums\CareerKey;
use App\Models\Character;
use App\Models\CharacterCareer;
use Carbon\CarbonInterface;

final class CareerHistoryWriter
{
    public function replaceCurrent(Character $character, string $career, CarbonInterface $date): ?CharacterCareer
    {
        $current = CharacterCareer::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->first();

        if ($current && $current->career_key === $career) {
            return $current;
        }

        if ($current) {
            $current->ended_date = $date->toDateString();
            $current->is_current = null;
            $current->save();
        }

        if ($career === CareerKey::NONE) {
            return null;
        }

        return CharacterCareer::query()->create([
            'world_id' => $character->world_id,
            'character_id' => $character->id,
            'career_key' => $career,
            'started_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }
}
