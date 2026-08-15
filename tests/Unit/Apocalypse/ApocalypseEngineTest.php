<?php

namespace Tests\Unit\Apocalypse;

use App\Domain\Apocalypse\AmbientDrift;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Apocalypse\ApocalypseMeters;
use App\Domain\Apocalypse\ConditionEvaluator;
use App\Domain\Apocalypse\MeterBoundPolicy;
use App\Domain\Apocalypse\PhaseAdvanceEvaluator;
use App\Domain\Apocalypse\PressureCalculator;
use App\Domain\Apocalypse\WorldSnapshot;
use PHPUnit\Framework\TestCase;

class ApocalypseEngineTest extends TestCase
{
    private function catalog(): ApocalypseCatalog
    {
        return ApocalypseCatalog::fromDirectory(dirname(__DIR__, 3).'/database/data/apocalypse');
    }

    public function test_ordinary_defaults_are_not_maximum_chaos(): void
    {
        $meters = new ApocalypseMeters($this->catalog()->startingMeters());

        $this->assertSame('ordinary', $this->catalog()->startingPhaseKey());
        $this->assertLessThan(10, $meters->get('global_corruption'));
        $this->assertSame(0, $meters->get('plague_severity'));
        $this->assertSame(0, $meters->get('demonic_manifestation'));
        $this->assertGreaterThan(70, $meters->get('church_cohesion'));
    }

    public function test_pressure_is_a_weighted_wound_score(): void
    {
        $healthy = new ApocalypseMeters($this->catalog()->startingMeters());
        $wounded = $healthy->withDeltas([
            'global_corruption' => 40,
            'plague_severity' => 40,
            'church_cohesion' => -40,
        ], [], []);

        $calc = new PressureCalculator([
            'global_corruption' => 0.18,
            'plague_severity' => 0.16,
            'demonic_manifestation' => 0.16,
            'institutional_collapse' => 0.12,
            'famine_pressure' => 0.10,
            'despair' => 0.12,
            'political_fragmentation' => 0.10,
            'church_cohesion_deficit' => 0.06,
        ]);

        $this->assertGreaterThan($calc->compute($healthy), $calc->compute($wounded));
    }

    public function test_floors_are_ratchets_not_averages(): void
    {
        $policy = new MeterBoundPolicy();
        $merged = $policy->mergeFloors(['plague_severity' => 10], ['plague_severity' => 4, 'despair' => 8]);

        $this->assertSame(10, $merged['plague_severity']);
        $this->assertSame(8, $merged['despair']);
    }

    public function test_phase_cannot_advance_on_pressure_alone_into_pestilence(): void
    {
        $catalog = $this->catalog();
        $evaluator = new PhaseAdvanceEvaluator($catalog, new ConditionEvaluator());
        $meters = new ApocalypseMeters(array_merge($catalog->startingMeters(), [
            'global_corruption' => 80,
            'despair' => 80,
        ]));
        $snapshot = new WorldSnapshot(
            'portents',
            2,
            $meters,
            70,
            [],
            [],
            400
        );

        $this->assertNull($evaluator->nextPhaseIfReady($snapshot));
    }

    public function test_pestilence_requires_plague_signals(): void
    {
        $catalog = $this->catalog();
        $evaluator = new PhaseAdvanceEvaluator($catalog, new ConditionEvaluator());
        $meters = new ApocalypseMeters(array_merge($catalog->startingMeters(), [
            'plague_severity' => 22,
            'global_corruption' => 12,
        ]));
        $snapshot = new WorldSnapshot(
            'portents',
            2,
            $meters,
            20,
            ['plague_mortality' => 1],
            [],
            14
        );

        $next = $evaluator->nextPhaseIfReady($snapshot);
        $this->assertNotNull($next);
        $this->assertSame('great_pestilence', $next['key']);
    }

    public function test_ambient_drift_accumulates_fractional_wounds(): void
    {
        $drift = new AmbientDrift();
        $step1 = $drift->step([], ['global_corruption' => 0.4]);
        $this->assertSame([], $step1['deltas']);
        $step2 = $drift->step($step1['accumulators'], ['global_corruption' => 0.4]);
        $this->assertSame([], $step2['deltas']);
        $step3 = $drift->step($step2['accumulators'], ['global_corruption' => 0.4]);
        $this->assertSame(['global_corruption' => 1], $step3['deltas']);
    }

    public function test_phase_catalog_is_ordered_and_terminal(): void
    {
        $ordinals = [];
        foreach ($this->catalog()->phases() as $phase) {
            $ordinals[] = $phase['ordinal'];
        }
        $sorted = $ordinals;
        sort($sorted);
        $this->assertSame($sorted, $ordinals);
        $this->assertNull($this->catalog()->phase('final_resistance')['advance_to']);
    }
}
