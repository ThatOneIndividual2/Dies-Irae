<?php

namespace App\Actions\Apocalypse;

use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Apocalypse\CoarseStageMap;
use App\Domain\Apocalypse\MeterBoundPolicy;
use App\Domain\Apocalypse\MilestoneEvaluator;
use App\Domain\Apocalypse\PhaseAdvanceEvaluator;
use App\Domain\Apocalypse\PressureCalculator;
use App\Domain\Apocalypse\WorldSnapshot;
use App\Domain\Support\AfterCommit;
use App\Events\ApocalypseMilestoneReached;
use App\Events\ApocalypsePhaseAdvanced;
use App\Models\ApocalypseChronicleEntry;
use App\Models\ApocalypseMilestoneRecord;
use App\Models\ApocalypseSignal;
use App\Models\ApocalypseState;
use App\Models\World;
use Carbon\Carbon;

/**
 * Single mutation owner for apocalypse_states. Callers must already be in a
 * transaction holding a lock on the state row.
 */
final class ApocalypseStateMutator
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private PressureCalculator $pressure,
        private MeterBoundPolicy $bounds,
        private PhaseAdvanceEvaluator $phases,
        private MilestoneEvaluator $milestones
    ) {
    }

    /**
     * @param  array<string, int>  $deltas
     * @return array<string, mixed>
     */
    public function applyMeterDeltas(World $world, ApocalypseState $state, array $deltas, string $reason): array
    {
        $floors = $state->meter_floors ?? [];
        $ceilings = $state->meter_ceilings ?? [];
        $before = $state->meters();
        $after = $before->withDeltas($deltas, $floors, $ceilings);
        $state->fillMeters($after->all());
        $state->pressure = $this->pressure->compute($after);
        $state->save();

        $evaluation = $this->evaluateProgression($world, $state->fresh());

        return [
            'before' => $before->all(),
            'after' => $after->all(),
            'deltas' => $deltas,
            'reason' => $reason,
            'phase' => $evaluation['phase'],
            'milestones' => $evaluation['milestones'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateProgression(World $world, ApocalypseState $state): array
    {
        $reached = [];
        $phaseChanges = [];

        // Milestones first so phase rules can require them in the same pass.
        for ($i = 0; $i < 8; $i++) {
            $snapshot = $this->snapshot($world, $state);
            $newMilestones = $this->milestones->newlyReached($snapshot);
            if ($newMilestones === []) {
                break;
            }
            foreach ($newMilestones as $milestone) {
                $reached[] = $this->recordMilestone($world, $state, $milestone);
                $state = $state->fresh();
            }
        }

        for ($i = 0; $i < 8; $i++) {
            $snapshot = $this->snapshot($world, $state);
            $next = $this->phases->nextPhaseIfReady($snapshot);
            if ($next === null) {
                break;
            }
            $phaseChanges[] = $this->advancePhase($world, $state, $next);
            $state = $state->fresh();
        }

        return [
            'phase' => $state->phase_key,
            'ordinal' => $state->phase_ordinal,
            'pressure' => $state->pressure,
            'milestones' => $reached,
            'phase_changes' => $phaseChanges,
        ];
    }

    public function snapshot(World $world, ApocalypseState $state): WorldSnapshot
    {
        $counts = ApocalypseSignal::query()
            ->where('world_id', $world->id)
            ->selectRaw('signal_key, COUNT(*) as aggregate')
            ->groupBy('signal_key')
            ->pluck('aggregate', 'signal_key')
            ->map(fn ($n) => (int) $n)
            ->all();

        $keys = ApocalypseMilestoneRecord::query()
            ->where('world_id', $world->id)
            ->pluck('milestone_key')
            ->all();

        $daysInPhase = Carbon::parse($state->phase_entered_on->toDateString())
            ->diffInDays(Carbon::parse($world->current_date->toDateString()));

        $meters = $state->meters();

        return new WorldSnapshot(
            $state->phase_key,
            (int) $state->phase_ordinal,
            $meters,
            $this->pressure->compute($meters),
            $counts,
            $keys,
            $daysInPhase
        );
    }

    /**
     * @param  array<string, mixed>  $milestone
     * @return array<string, mixed>
     */
    public function recordMilestone(World $world, ApocalypseState $state, array $milestone): array
    {
        $existing = ApocalypseMilestoneRecord::query()
            ->where('world_id', $world->id)
            ->where('milestone_key', $milestone['key'])
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return ['skipped' => true, 'key' => $milestone['key']];
        }

        $floors = $this->bounds->mergeFloors($state->meter_floors ?? [], $milestone['apply_floors'] ?? []);
        $ceilings = $this->bounds->mergeCeilings($state->meter_ceilings ?? [], $milestone['apply_ceilings'] ?? []);

        $record = ApocalypseMilestoneRecord::query()->create([
            'world_id' => $world->id,
            'milestone_key' => $milestone['key'],
            'name' => $milestone['name'],
            'reached_on' => $world->current_date->toDateString(),
            'phase_key' => $state->phase_key,
            'apply_floors' => $milestone['apply_floors'] ?? [],
            'apply_ceilings' => $milestone['apply_ceilings'] ?? [],
            'meters_at_reach' => $state->meters()->all(),
            'irreversible' => (bool) ($milestone['irreversible'] ?? true),
        ]);

        $state->meter_floors = $floors;
        $state->meter_ceilings = $ceilings;
        $clamped = $state->meters()->clampedTo($floors, $ceilings);
        $state->fillMeters($clamped->all());
        $state->pressure = $this->pressure->compute($clamped);
        $state->save();

        ApocalypseChronicleEntry::query()->create([
            'world_id' => $world->id,
            'world_date' => $world->current_date->toDateString(),
            'entry_type' => 'milestone',
            'subject_key' => $milestone['key'],
            'title' => $milestone['name'],
            'body' => $milestone['chronicle'] ?? null,
            'payload' => [
                'floors' => $floors,
                'ceilings' => $ceilings,
            ],
        ]);

        AfterCommit::dispatch(function () use ($world, $milestone) {
            ApocalypseMilestoneReached::dispatch(
                $world,
                $milestone['key'],
                $world->current_date->toDateString()
            );
        });

        return ['key' => $milestone['key'], 'id' => $record->id];
    }

    /**
     * @param  array<string, mixed>  $next
     * @return array<string, mixed>
     */
    public function advancePhase(World $world, ApocalypseState $state, array $next): array
    {
        $from = $state->phase_key;
        if ((int) $next['ordinal'] <= (int) $state->phase_ordinal) {
            return ['skipped' => true, 'reason' => 'not_forward'];
        }

        $floors = $this->bounds->mergeFloors($state->meter_floors ?? [], $next['meter_floors'] ?? []);
        $ceilings = $state->meter_ceilings ?? [];
        if (isset($next['church_cohesion_ceiling'])) {
            $ceilings = $this->bounds->mergeCeilings($ceilings, [
                'church_cohesion' => (int) $next['church_cohesion_ceiling'],
            ]);
        }

        $state->phase_key = $next['key'];
        $state->phase_ordinal = (int) $next['ordinal'];
        $state->phase_entered_on = $world->current_date->toDateString();
        $state->stage = CoarseStageMap::stageFromPhase($next['key']);
        $state->stage_entered_date = $world->current_date->toDateString();
        $state->meter_floors = $floors;
        $state->meter_ceilings = $ceilings;
        $state->broken_assumptions = $next['broken_assumptions'] ?? [];
        $clamped = $state->meters()->clampedTo($floors, $ceilings);
        $state->fillMeters($clamped->all());
        $state->pressure = $this->pressure->compute($clamped);
        $state->save();

        ApocalypseChronicleEntry::query()->create([
            'world_id' => $world->id,
            'world_date' => $world->current_date->toDateString(),
            'entry_type' => 'phase',
            'subject_key' => $next['key'],
            'title' => $next['name'],
            'body' => $next['summary'] ?? null,
            'payload' => [
                'from' => $from,
                'to' => $next['key'],
                'ordinal' => $next['ordinal'],
                'floors' => $floors,
            ],
        ]);

        AfterCommit::dispatch(function () use ($world, $from, $next) {
            ApocalypsePhaseAdvanced::dispatch(
                $world,
                $from,
                $next['key'],
                $world->current_date->toDateString()
            );
        });

        return ['from' => $from, 'to' => $next['key']];
    }
}
