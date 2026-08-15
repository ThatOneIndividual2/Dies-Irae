<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\CorruptionService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\CorruptionChanged;
use App\Models\CorruptionState;
use App\Models\World;
use Carbon\CarbonInterface;

final class ApplyCorruption
{
    public function __construct(private CorruptionService $corruption)
    {
    }

    public function execute(
        World $world,
        string $subjectType,
        int $subjectId,
        string $kind,
        int $intensityDelta,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $visibleSigns = null
    ): CorruptionState {
        return Transactional::run(function () use (
            $world, $subjectType, $subjectId, $kind, $intensityDelta, $date, $sourceType, $sourceId, $visibleSigns
        ) {
            $state = $this->corruption->apply(
                $world,
                $subjectType,
                $subjectId,
                $kind,
                $intensityDelta,
                $date,
                $sourceType,
                $sourceId,
                $visibleSigns
            );

            AfterCommit::dispatch(fn () => event(new CorruptionChanged(
                $state->id,
                $state->subject_type,
                $state->subject_id,
                (int) $state->intensity
            )));

            return $state;
        });
    }
}
