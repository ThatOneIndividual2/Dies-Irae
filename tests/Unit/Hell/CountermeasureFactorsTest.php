<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\CountermeasureAttempt;
use App\Domain\Hell\Enums\CountermeasureKind;
use App\Domain\Hell\Enums\CountermeasureResult;
use App\Domain\Hell\Enums\IncursionState;
use PHPUnit\Framework\TestCase;

final class CountermeasureFactorsTest extends TestCase
{
    public function test_devotion_alone_does_not_exorcise_a_wounded_county(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 60;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::MANIFESTED;
        $t->corruption = 90;
        $t->despair = 85;
        $t->localManifestation = 70;
        DemonicThreatHarness::withCult($world, 'center', 80);
        DemonicThreatHarness::withNamed($world, 'apollyon-1', 'apollyon_the_unmaker', 'manifested', 'center');

        $attempt = new CountermeasureAttempt(CountermeasureKind::EXORCISM, 'center');
        $attempt->actorCharacterId = 'priest-1';
        $attempt->factors = [
            'clergy_present' => true,
            'clergy_presence' => 5,
            'devotional_standing' => 100,
            'clergy_rank_weight' => 10,
            'clergy_legitimacy' => 10,
            'relic_authenticity' => 0,
            'relic_power' => 0,
            'holy_order_support' => 0,
            'accumulated_prayer' => 0,
        ];

        $outcome = $engine->attemptCountermeasure($world, $attempt);

        $this->assertNotSame(CountermeasureResult::SUCCESS, $outcome->result);
        $this->assertContains($outcome->result, [
            CountermeasureResult::FAILURE,
            CountermeasureResult::BACKLASH,
            CountermeasureResult::PARTIAL,
        ]);
    }

    public function test_combined_church_strength_can_succeed_where_devotion_alone_fails(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 20;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::MANIFESTED;
        $t->corruption = 30;
        $t->despair = 20;
        DemonicThreatHarness::withPossession($world, 'lord-center');

        $attempt = new CountermeasureAttempt(CountermeasureKind::EXORCISM, 'center');
        $attempt->actorCharacterId = 'bishop-1';
        $attempt->targetCharacterId = 'lord-center';
        $attempt->factors = [
            'clergy_present' => true,
            'clergy_rank_weight' => 80,
            'clergy_legitimacy' => 90,
            'relic_authenticity' => 95,
            'relic_power' => 80,
            'holy_order_support' => 70,
            'accumulated_prayer' => 60,
            'devotional_standing' => 40,
        ];

        $outcome = $engine->attemptCountermeasure($world, $attempt);

        $this->assertSame(CountermeasureResult::SUCCESS, $outcome->result);
        $this->assertSame(IncursionState::TEMPTED, $world->territory('center')->incursionState);
    }

    public function test_identical_factors_are_deterministic(): void
    {
        $make = function () {
            $engine = DemonicThreatHarness::engine();
            $world = DemonicThreatHarness::ordinaryCounties();
            $world->apocalypseIntensity = 25;
            $world->territory('center')->incursionState = IncursionState::CORRUPTED;
            $world->territory('center')->corruption = 50;
            $attempt = new CountermeasureAttempt(CountermeasureKind::PRAYER, 'center');
            $attempt->actorCharacterId = 'monk-1';
            $attempt->factors = [
                'accumulated_prayer' => 70,
                'clergy_presence' => 40,
                'local_faith' => 55,
                'devotional_standing' => 40,
            ];

            return $engine->attemptCountermeasure($world, $attempt);
        };

        $a = $make();
        $b = $make();
        $this->assertSame($a->result, $b->result);
        $this->assertEquals($a->score, $b->score);
    }

    public function test_martyrdom_can_succeed_where_prayer_does_not(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 50;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::BREACHED;
        $t->corruption = 70;
        $t->despair = 60;
        $engine->openBreach($world, 'center');

        $prayer = new CountermeasureAttempt(CountermeasureKind::PRAYER, 'center');
        $prayer->actorCharacterId = 'priest-1';
        $prayer->factors = [
            'accumulated_prayer' => 20,
            'clergy_presence' => 10,
            'local_faith' => 15,
            'devotional_standing' => 10,
        ];
        $prayerOutcome = $engine->attemptCountermeasure($world->duplicate(), $prayer);

        $martyr = new CountermeasureAttempt(CountermeasureKind::MARTYRDOM, 'center');
        $martyr->actorCharacterId = 'priest-1';
        $martyr->factors = [
            'martyrdom_offered' => 100,
            'clergy_legitimacy' => 80,
            'local_faith' => 15,
            'relic_authenticity' => 40,
        ];
        $martyrWorld = $world->duplicate();
        $engine->openBreach($martyrWorld, 'center');
        $martyrOutcome = $engine->attemptCountermeasure($martyrWorld, $martyr);

        $this->assertNotSame(CountermeasureResult::SUCCESS, $prayerOutcome->result);
        $this->assertSame(CountermeasureResult::SUCCESS, $martyrOutcome->result);
        $this->assertTrue($martyrOutcome->characterDied);
        $this->assertTrue($martyrOutcome->breachClosed);
    }
}
