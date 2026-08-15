<?php

namespace App\Actions\Apocalypse;

use App\Actions\Time\ScheduleWorldEvent;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Apocalypse\ApocalypseMeters;
use App\Domain\Apocalypse\CoarseStageMap;
use App\Domain\Apocalypse\PressureCalculator;
use App\Domain\Support\Transactional;
use App\Models\ApocalypseState;
use App\Models\World;
use Carbon\Carbon;

final class EnsureApocalypseState
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private PressureCalculator $pressure,
        private ScheduleWorldEvent $scheduleEvent
    ) {
    }

    public function execute(World $world): ApocalypseState
    {
        return Transactional::run(function () use ($world) {
            $existing = ApocalypseState::query()
                ->where('world_id', $world->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $phase = $this->catalog->phase($this->catalog->startingPhaseKey());
            $meters = $this->catalog->startingMeters();
            $date = $world->current_date->toDateString();

            $state = ApocalypseState::query()->create([
                'world_id' => $world->id,
                'phase_key' => $phase['key'],
                'phase_ordinal' => $phase['ordinal'],
                'phase_entered_on' => $date,
                'stage' => CoarseStageMap::stageFromPhase($phase['key']),
                'stage_entered_date' => $date,
                'signs' => [],
                'pressure' => 0,
                'meter_floors' => $phase['meter_floors'] ?? [],
                'meter_ceilings' => $this->startingCeilings($phase),
                'drift_accumulators' => [],
                'broken_assumptions' => $phase['broken_assumptions'] ?? [],
                'tick_count' => 0,
                'last_ticked_on' => null,
            ]);
            $state->fillMeters($meters);
            $state->pressure = $this->pressure->compute($state->meters());
            $state->save();

            $interval = (int) config('apocalypse.tick_interval_days', 7);
            $this->scheduleEvent->execute(
                $world,
                'apocalypse_stage_check',
                Carbon::parse($date)->addDays($interval),
                ['reason' => 'initial'],
                'apocalypse_tick:'.$world->id.':'.Carbon::parse($date)->addDays($interval)->toDateString()
            );

            return $state->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $phase
     * @return array<string, int>
     */
    private function startingCeilings(array $phase): array
    {
        $ceilings = [];
        foreach (ApocalypseMeters::KEYS as $key) {
            $ceilings[$key] = 100;
        }
        if (isset($phase['church_cohesion_ceiling'])) {
            $ceilings['church_cohesion'] = (int) $phase['church_cohesion_ceiling'];
        }

        return $ceilings;
    }
}
