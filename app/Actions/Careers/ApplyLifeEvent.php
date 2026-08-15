<?php

namespace App\Actions\Careers;

use App\Domain\Careers\CareerEngine;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterCareerEvent;
use App\Models\CharacterLifeState;
use Carbon\CarbonInterface;

final class ApplyLifeEvent
{
    public function __construct(private CareerEngine $engine)
    {
    }

    public function execute(Character $character, string $event, CarbonInterface $date, int $age, array $payload = []): ?CharacterLifeState
    {
        WorldBoundary::assertSameWorldEntities('career life event', $character);

        return Transactional::run(function () use ($character, $event, $date, $age, $payload) {
            $person = (new CareerPersonFactory())->fromCharacter($character, $age);
            $this->engine->apply($person, $event, $payload);
            (new CareerHistoryWriter())->replaceCurrent($character, $person->career, $date);

            foreach ($person->lifeStates as $state) {
                $exists = CharacterLifeState::query()
                    ->where('character_id', $character->id)
                    ->where('state_key', $state)
                    ->where('is_current', true)
                    ->first();
                if ($exists) {
                    continue;
                }
                CharacterLifeState::query()->create([
                    'world_id' => $character->world_id,
                    'character_id' => $character->id,
                    'state_key' => $state,
                    'started_date' => $date->toDateString(),
                    'is_current' => true,
                ]);
            }

            $currentStates = CharacterLifeState::query()
                ->where('character_id', $character->id)
                ->where('is_current', true)
                ->get();
            foreach ($currentStates as $row) {
                if (!in_array($row->state_key, $person->lifeStates, true)) {
                    $row->ended_date = $date->toDateString();
                    $row->is_current = null;
                    $row->save();
                }
            }

            foreach ($person->skills->toArray() as $key => $value) {
                $character->{$key} = $value;
            }
            $character->save();

            CharacterCareerEvent::query()->create([
                'world_id' => $character->world_id,
                'character_id' => $character->id,
                'event_key' => $event,
                'payload' => $payload,
                'occurred_date' => $date->toDateString(),
            ]);

            return CharacterLifeState::query()
                ->where('character_id', $character->id)
                ->orderByDesc('id')
                ->first();
        });
    }
}
