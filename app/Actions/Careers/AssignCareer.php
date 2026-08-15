<?php

namespace App\Actions\Careers;

use App\Domain\Careers\CareerEngine;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterCareer;
use Carbon\CarbonInterface;
use RuntimeException;

final class AssignCareer
{
    public function __construct(private CareerEngine $engine)
    {
    }

    public function execute(Character $character, string $career, CarbonInterface $date, int $age, bool $forced = false): CharacterCareer
    {
        WorldBoundary::assertSameWorldEntities('assign career', $character);

        return Transactional::run(function () use ($character, $career, $date, $age, $forced) {
            $person = (new CareerPersonFactory())->fromCharacter($character, $age);
            $this->engine->assignCareer($person, $career, $forced);
            $this->writeSkills($character, $person);
            $row = (new CareerHistoryWriter())->replaceCurrent($character, $career, $date);
            if ($row === null) {
                throw new RuntimeException('Career assignment produced no current row');
            }

            return $row;
        });
    }

    private function writeSkills(Character $character, $person): void
    {
        foreach ($person->skills->toArray() as $key => $value) {
            $character->{$key} = $value;
        }
        $character->save();
    }
}
