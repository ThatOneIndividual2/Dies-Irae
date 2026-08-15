<?php

namespace App\Actions\Apocalypse;

use App\Actions\Time\ScheduleWorldEvent;
use App\Domain\Apocalypse\AmbientDrift;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Support\Transactional;
use App\Models\ApocalypseState;
use App\Models\World;
use Carbon\Carbon;

final class ProcessApocalypseTick
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private AmbientDrift $drift,
        private EnsureApocalypseState $ensure,
        private ApocalypseStateMutator $mutator,
        private ScheduleWorldEvent $scheduleEvent
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world, bool $force = false): array
    {
        return Transactional::run(function () use ($world, $force) {
            $this->ensure->execute($world);

            $state = ApocalypseState::query()
                ->where('world_id', $world->id)
                ->lockForUpdate()
                ->firstOrFail();

            $today = $world->current_date->toDateString();
            if (!$force && $state->last_ticked_on && $state->last_ticked_on->toDateString() === $today) {
                return [
                    'skipped' => true,
                    'reason' => 'already_ticked',
                    'date' => $today,
                    'phase' => $state->phase_key,
                    'pressure' => $state->pressure,
                ];
            }

            $phase = $this->catalog->phase($state->phase_key);
            $step = $this->drift->step(
                $state->drift_accumulators ?? [],
                $phase['ambient_drift'] ?? []
            );

            if ($step['deltas'] !== []) {
                $driftResult = $this->mutator->applyMeterDeltas($world, $state->fresh(), $step['deltas'], 'ambient_drift');
            } else {
                $driftResult = array_merge(
                    ['deltas' => []],
                    $this->mutator->evaluateProgression($world, $state->fresh())
                );
            }

            $state = $state->fresh();
            $state->drift_accumulators = $step['accumulators'];
            $state->tick_count = (int) $state->tick_count + 1;
            $state->last_ticked_on = $today;
            $state->save();

            $interval = (int) config('apocalypse.tick_interval_days', 7);
            $nextDate = Carbon::parse($today)->addDays($interval);
            $this->scheduleEvent->execute(
                $world,
                'apocalypse_stage_check',
                $nextDate,
                ['reason' => 'tick'],
                'apocalypse_tick:'.$world->id.':'.$nextDate->toDateString()
            );

            return [
                'skipped' => false,
                'date' => $today,
                'tick_count' => $state->tick_count,
                'phase' => $state->phase_key,
                'ordinal' => $state->phase_ordinal,
                'pressure' => $state->pressure,
                'meters' => $state->meters()->all(),
                'floors' => $state->meter_floors,
                'drift' => $driftResult['deltas'] ?? [],
                'milestones' => $driftResult['milestones'] ?? [],
                'phase_changes' => $driftResult['phase_changes'] ?? [],
            ];
        });
    }
}
