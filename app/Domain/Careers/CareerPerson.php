<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\LifeStateKey;

final class CareerPerson
{
    public int $worldId;
    public string $id;
    public string $name;
    public int $age;
    public CharacterSkills $skills;
    public string $career;
    /** @var list<array{career:string,at_age:int}> */
    public array $careerHistory = [];
    /** @var list<string> */
    public array $lifeStates = [];
    /** @var list<array{state:string,at_age:int,ended_age:?int}> */
    public array $lifeStateHistory = [];
    /** @var list<string> */
    public array $educations = [];
    public ?string $titleRank = null;
    public bool $hasClaim = false;
    /** @var list<array{age:int,kind:string,detail:string}> */
    public array $log = [];

    public function __construct(int $worldId, string $id, string $name, int $age, ?CharacterSkills $skills = null)
    {
        $this->worldId = $worldId;
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
        $this->skills = $skills ?? new CharacterSkills();
        $this->career = CareerKey::NONE;
    }

    public function hasLifeState(string $state): bool
    {
        return in_array($state, $this->lifeStates, true);
    }

    public function addLifeState(string $state): void
    {
        $exclusive = LifeStateRules::exclusiveWith()[$state] ?? [];
        foreach ($exclusive as $other) {
            $this->endLifeState($other);
        }
        if (!$this->hasLifeState($state)) {
            $this->lifeStates[] = $state;
            $this->lifeStateHistory[] = ['state' => $state, 'at_age' => $this->age, 'ended_age' => null];
        }
    }

    public function endLifeState(string $state): void
    {
        $this->lifeStates = array_values(array_filter($this->lifeStates, function ($current) use ($state) {
            return $current !== $state;
        }));
        for ($i = count($this->lifeStateHistory) - 1; $i >= 0; $i--) {
            if ($this->lifeStateHistory[$i]['state'] === $state && $this->lifeStateHistory[$i]['ended_age'] === null) {
                $this->lifeStateHistory[$i]['ended_age'] = $this->age;
                break;
            }
        }
    }

    public function record(string $kind, string $detail): void
    {
        $this->log[] = ['age' => $this->age, 'kind' => $kind, 'detail' => $detail];
    }

    public function careerKeys(): array
    {
        $keys = array_map(function ($row) {
            return $row['career'];
        }, $this->careerHistory);
        $keys[] = $this->career;

        return array_values(array_unique($keys));
    }

    public function publicSkills(): array
    {
        return $this->skills->toArray();
    }

    public function snapshot(): array
    {
        return [
            'id' => $this->id,
            'age' => $this->age,
            'career' => $this->career,
            'career_history' => $this->careerKeys(),
            'life_states' => $this->lifeStates,
            'educations' => $this->educations,
            'title_rank' => $this->titleRank,
            'skills' => $this->publicSkills(),
            'piety_reputation' => $this->skills->pietyReputation(),
            'has_claim' => $this->hasClaim,
            'log' => $this->log,
        ];
    }

    public function isCleric(): bool
    {
        return CareerKey::isClerical($this->career) || $this->hasLifeState(LifeStateKey::IN_CLERGY);
    }
}
