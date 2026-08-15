<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\CanonicalStandingService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\CanonicalStandingChanged;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\World;

final class RecordCanonicalCensure
{
    public function __construct(private CanonicalStandingService $canonical)
    {
    }

    public function execute(
        World $world,
        Character $character,
        string $censure,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): CharacterCanonicalState {
        return Transactional::run(function () use ($world, $character, $censure, $sourceType, $sourceId) {
            $state = $this->canonical->setCensure($world, $character, $censure, $sourceType, $sourceId);

            AfterCommit::dispatch(fn () => event(new CanonicalStandingChanged(
                $character->id,
                $state->censure,
                $state->eucharist_standing
            )));

            return $state;
        });
    }
}
