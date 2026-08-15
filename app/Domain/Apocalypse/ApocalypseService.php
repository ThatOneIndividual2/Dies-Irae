<?php

namespace App\Domain\Apocalypse;

use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Contracts\ApocalypseReporter;
use App\Domain\Hell\Ports\ApocalypseReading;
use App\Models\ApocalypseState;
use App\Models\World;

final class ApocalypseService implements ApocalypseContract, ApocalypseReading, ApocalypseReporter
{
    public function __construct(private RecordApocalypseSignal $signals)
    {
    }

    public function domainKey(): string
    {
        return 'apocalypse';
    }

    public function record(World $world, string $signalKey, int $magnitude, array $context = []): \App\Models\ApocalypseSignal
    {
        return $this->signals->execute($world, $signalKey, $magnitude, $context);
    }

    public function intensity(int $worldId): int
    {
        $state = ApocalypseState::query()->where('world_id', $worldId)->first();

        return $state ? (int) $state->pressure : 0;
    }

    public function phaseKey(int $worldId): string
    {
        $state = ApocalypseState::query()->where('world_id', $worldId)->first();
        if (!$state) {
            return 'ordinary';
        }

        return $state->phase_key ?: CoarseStageMap::phaseFromStage((string) ($state->stage ?? 'ordinary_order'));
    }
}
