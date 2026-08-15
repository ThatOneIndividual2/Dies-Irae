<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\RespawnPolicy;
use PHPUnit\Framework\TestCase;

final class NamedDemonPersistenceTest extends TestCase
{
    public function test_destroyed_named_demon_does_not_return_on_pulse(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 90;
        DemonicThreatHarness::withNamed($world, 'apollyon-1', 'apollyon_the_unmaker', NamedDemonStatus::MANIFESTED, 'center');

        $engine->destroyNamedDemon($world, 'apollyon-1', 'exorcism');
        $engine->pulse($world);
        $engine->pulse($world);

        $demon = $world->namedDemons['apollyon-1'];
        $this->assertSame(NamedDemonStatus::DESTROYED, $demon->status);
        $this->assertNull($demon->territoryId);
        $this->assertFalse($demon->returnAuthorized);
    }

    public function test_never_policy_refuses_lore_permission(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        DemonicThreatHarness::withNamed($world, 'apollyon-1', 'apollyon_the_unmaker');
        $engine->destroyNamedDemon($world, 'apollyon-1', 'martyrdom');

        $this->expectException(\InvalidArgumentException::class);
        $engine->permitNamedDemonReturn($world, 'apollyon-1', 'the stars demand it', 'lore');
    }

    public function test_lore_only_returns_only_after_explicit_permission(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        DemonicThreatHarness::withNamed($world, 'mammon-1', 'mammon_the_gilded', NamedDemonStatus::MANIFESTED, 'east');
        $this->assertSame(RespawnPolicy::LORE_ONLY, $world->namedDemons['mammon-1']->respawnPolicy);

        $engine->destroyNamedDemon($world, 'mammon-1', 'relic');
        $engine->pulse($world);
        $this->assertSame(NamedDemonStatus::DESTROYED, $world->namedDemons['mammon-1']->status);

        $engine->permitNamedDemonReturn($world, 'mammon-1', 'A sealed chronicle names the hour of his second bargaining.', 'lore');
        $engine->named()->enactAuthorizedReturn($world, 'mammon-1', 'east');

        $this->assertSame(NamedDemonStatus::MANIFESTED, $world->namedDemons['mammon-1']->status);
        $this->assertSame('east', $world->namedDemons['mammon-1']->territoryId);
    }

    public function test_duplicate_living_named_instance_is_rejected(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        DemonicThreatHarness::withNamed($world, 'mammon-1', 'mammon_the_gilded');

        $this->expectException(\InvalidArgumentException::class);
        $engine->named()->spawnLatent($world, 'mammon-2', 'mammon_the_gilded');
    }
}
