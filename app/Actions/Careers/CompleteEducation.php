<?php

namespace App\Actions\Careers;

use App\Domain\Careers\CareerEngine;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterEducation;
use Carbon\CarbonInterface;

final class CompleteEducation
{
    public function __construct(private CareerEngine $engine)
    {
    }

    public function execute(Character $character, string $source, CarbonInterface $started, CarbonInterface $ended, int $age): CharacterEducation
    {
        WorldBoundary::assertSameWorldEntities('complete education', $character);

        return Transactional::run(function () use ($character, $source, $started, $ended, $age) {
            $person = (new CareerPersonFactory())->fromCharacter($character, $age);
            $this->engine->completeEducation($person, $source);
            foreach ($person->skills->toArray() as $key => $value) {
                $character->{$key} = $value;
            }
            $character->save();

            return CharacterEducation::query()->create([
                'world_id' => $character->world_id,
                'character_id' => $character->id,
                'source' => $source,
                'started_date' => $started->toDateString(),
                'ended_date' => $ended->toDateString(),
                'is_complete' => true,
            ]);
        });
    }
}
