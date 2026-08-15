<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\Enums\IncursionState;
use PHPUnit\Framework\TestCase;

final class DeterminismTest extends TestCase
{
    public function test_two_pulses_from_cloned_worlds_match(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 55;
        $world->territory('center')->incursionState = IncursionState::BREACHED;
        $world->territory('center')->corruption = 60;
        $world->territory('center')->despair = 55;
        $world->territory('center')->morale = 20;
        $world->territory('center')->localManifestation = 70;
        $world->territory('center')->plague = 30;
        DemonicThreatHarness::withCult($world, 'center', 40);
        $engine->openBreach($world, 'center');
        DemonicThreatHarness::withArmy($world, 'levy-1', 'center', 45);

        $a = $world->duplicate();
        $b = $world->duplicate();

        $engine->pulse($a);
        $engine->pulse($b);

        $this->assertSame($a->fingerprint(), $b->fingerprint());
    }

    public function test_different_seeds_diverge_on_host_spawn_roll(): void
    {
        $rng = new \App\Domain\Hell\DeterministicRng();
        $a = $rng->float('seed-alpha', 1, 'hell_host_spawn', '1348-06-01', 'center');
        $b = $rng->float('seed-omega', 1, 'hell_host_spawn', '1348-06-01', 'center');

        $this->assertNotEquals($a, $b);
    }
}
