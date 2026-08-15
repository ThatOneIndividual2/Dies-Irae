<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\CorruptionService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\CorruptionChanged;
use App\Models\CorruptionState;
use App\Models\World;
use Carbon\CarbonInterface;

final class CleanseCorruption
{
    public function __construct(private CorruptionService $corruption)
    {
    }

    public function execute(
        World $world,
        string $subjectType,
        int $subjectId,
        int $amount,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): ?CorruptionState {
        return Transactional::run(function () use ($world, $subjectType, $subjectId, $amount, $date, $sourceType, $sourceId) {
            $state = $this->corruption->cleanse($world, $subjectType, $subjectId, $amount, $date, $sourceType, $sourceId);
            if ($state) {
                AfterCommit::dispatch(fn () => event(new CorruptionChanged(
                    $state->id,
                    $state->subject_type,
                    $state->subject_id,
                    (int) $state->intensity
                )));
            }

            return $state;
        });
    }
}
