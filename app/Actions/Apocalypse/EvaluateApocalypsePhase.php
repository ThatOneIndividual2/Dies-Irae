<?php

namespace App\Actions\Apocalypse;

use App\Domain\Support\Transactional;
use App\Models\ApocalypseState;
use App\Models\World;

final class EvaluateApocalypsePhase
{
    public function __construct(
        private EnsureApocalypseState $ensure,
        private ApocalypseStateMutator $mutator
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world): array
    {
        return Transactional::run(function () use ($world) {
            $this->ensure->execute($world);
            $state = ApocalypseState::query()
                ->where('world_id', $world->id)
                ->lockForUpdate()
                ->firstOrFail();

            return $this->mutator->evaluateProgression($world, $state);
        });
    }
}
