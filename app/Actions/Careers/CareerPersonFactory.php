<?php

namespace App\Actions\Careers;

use App\Domain\Careers\CareerEngine;
use App\Domain\Careers\CareerPerson;
use App\Domain\Enums\CareerKey;
use App\Domain\Enums\LifeStateKey;
use App\Domain\Enums\SkillKey;
use App\Models\Character;
use App\Models\CharacterCareer;
use App\Models\CharacterEducation;
use App\Models\CharacterLifeState;

final class CareerPersonFactory
{
    public function fromCharacter(Character $character, int $age): CareerPerson
    {
        $skills = [];
        foreach (SkillKey::all() as $key) {
            if (isset($character->{$key})) {
                $skills[$key] = (int) $character->{$key};
            }
        }

        $person = (new CareerEngine())->person(
            (int) $character->world_id,
            (string) $character->id,
            $character->displayName(),
            $age,
            $skills
        );

        $careers = CharacterCareer::query()
            ->where('character_id', $character->id)
            ->orderBy('id')
            ->get();
        foreach ($careers as $row) {
            if ($row->is_current) {
                $person->career = $row->career_key;
            } else {
                $person->careerHistory[] = [
                    'career' => $row->career_key,
                    'at_age' => $age,
                ];
            }
        }
        if ($person->career === CareerKey::NONE && $careers->isNotEmpty()) {
            $last = $careers->last();
            $person->career = $last->career_key;
        }

        $states = CharacterLifeState::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->pluck('state_key')
            ->all();
        $person->lifeStates = array_values($states);
        $person->hasClaim = $person->hasLifeState(LifeStateKey::CLAIMANT);

        $person->educations = CharacterEducation::query()
            ->where('character_id', $character->id)
            ->where('is_complete', true)
            ->pluck('source')
            ->all();

        return $person;
    }
}
