<?php

namespace Tests\Unit\Hell;

use App\Domain\Enums\OverlayState;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\IncursionLifecycle;
use PHPUnit\Framework\TestCase;

final class IncursionLifecycleTest extends TestCase
{
    public function test_ordinary_world_can_tempt_but_cannot_breach(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 0;

        $t = $world->territory('center');
        $t->corruption = 80;
        $t->despair = 80;
        $t->cultActivity = 80;
        $t->localManifestation = 90;
        DemonicThreatHarness::withCult($world, 'center', 80);

        $engine->pulse($world);

        $this->assertSame(IncursionState::TEMPTED, $world->territory('center')->incursionState);
        $this->assertNotSame(IncursionState::BREACHED, $world->territory('center')->incursionState);
        $this->assertSame('county-center', $world->territory('center')->legalTitleId);
    }

    public function test_corruption_and_cult_advance_tempted_to_corrupted_when_veil_thins(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 12;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::TEMPTED;
        DemonicThreatHarness::ripeForCorruption($world, 'center');

        $engine->pulse($world);

        $this->assertSame(IncursionState::CORRUPTED, $world->territory('center')->incursionState);
        $this->assertTrue($world->territory('center')->settlementCorrupted);
        $this->assertSame('county-center', $world->territory('center')->legalTitleId);
    }

    public function test_named_demon_and_apocalypse_gate_manifestation(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 30;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::CORRUPTED;
        $t->corruption = 50;
        $t->localManifestation = 10;
        DemonicThreatHarness::withNamed($world, 'apollyon-1', 'apollyon_the_unmaker', 'manifested', 'center');

        $engine->pulse($world);

        $this->assertSame(IncursionState::MANIFESTED, $world->territory('center')->incursionState);
    }

    public function test_unnamed_incursion_can_manifest_without_a_named_demon(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 30;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::CORRUPTED;
        $t->corruption = 50;
        $t->localManifestation = 40;

        $this->assertSame([], $world->namedDemons);

        $engine->pulse($world);

        $this->assertSame(IncursionState::MANIFESTED, $world->territory('center')->incursionState);
        $this->assertSame([], $world->namedDemons);
        $this->assertSame('county-center', $world->territory('center')->legalTitleId);
    }

    public function test_lifecycle_config_order_covers_all_states(): void
    {
        $life = IncursionLifecycle::load(DemonicThreatHarness::dataDir().'/incursion_states.json');
        foreach (IncursionState::all() as $state) {
            $this->assertNotSame([], $life->effects($state));
        }
    }

    public function test_incursion_maps_to_coarse_overlay_without_replacing_legal_geography(): void
    {
        $this->assertSame(OverlayState::ORDINARY, IncursionState::overlayKind(IncursionState::DORMANT));
        $this->assertSame(OverlayState::BLIGHTED, IncursionState::overlayKind(IncursionState::TEMPTED));
        $this->assertSame(OverlayState::HAUNTED, IncursionState::overlayKind(IncursionState::CORRUPTED));
        $this->assertSame(OverlayState::RIFTED, IncursionState::overlayKind(IncursionState::BREACHED));
        $this->assertSame(OverlayState::OCCUPIED_BY_HELL, IncursionState::overlayKind(IncursionState::INFERNAL_STRONGHOLD));
    }
}
