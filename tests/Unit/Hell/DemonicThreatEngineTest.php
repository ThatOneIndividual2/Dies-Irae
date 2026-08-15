<?php

namespace Tests\Unit\Hell;

use App\Actions\Hell\PulseHellIncursion;
use App\Domain\Hell\Enums\HostKind;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\State\DemonicHost;
use PHPUnit\Framework\TestCase;

final class DemonicThreatEngineTest extends TestCase
{
    public function test_faction_is_not_a_realm_and_title_survives_rift(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 80;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::OVERRUN;
        $t->corruption = 90;
        $t->morale = 10;
        $t->legalTitleId = 'county-center';
        $engine->openBreach($world, 'center');
        DemonicThreatHarness::withNamed($world, 'apollyon-1', 'apollyon_the_unmaker', NamedDemonStatus::MANIFESTED, 'center');

        $engine->pulse($world);

        $this->assertSame('county-center', $world->territory('center')->legalTitleId);
        $world->assertNotARealm();
        foreach ($world->factions as $faction) {
            $this->assertArrayNotHasKey('realmId', $faction->toArray());
            $this->assertArrayNotHasKey('liegeId', $faction->toArray());
        }
        $this->assertContains($world->territory('center')->incursionState, [
            IncursionState::OVERRUN,
            IncursionState::INFERNAL_STRONGHOLD,
        ]);
    }

    public function test_neighbor_overrun_tempts_adjacent_dormant_land(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 70;
        $center = $world->territory('center');
        $center->incursionState = IncursionState::OVERRUN;
        $center->corruption = 90;
        $center->despair = 80;

        $engine->pulse($world);

        $this->assertSame(IncursionState::TEMPTED, $world->territory('west')->incursionState);
        $this->assertGreaterThan(0, $world->territory('west')->corruption);
        $this->assertFalse($world->territory('west')->travelOpen);
        $this->assertSame('county-west', $world->territory('west')->legalTitleId);
    }

    public function test_corrupted_army_is_not_a_demonic_host(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 40;
        $world->territory('center')->incursionState = IncursionState::MANIFESTED;
        DemonicThreatHarness::withArmy($world, 'levy-1', 'center', 12);

        $host = new DemonicHost('host-1', 'center', 'locust_host');
        $host->kind = HostKind::DEMONIC_HOST;
        $host->strength = 20;
        $world->hosts[$host->id] = $host;

        $engine->pulse($world);

        $this->assertFalse($world->armies['levy-1']->cleansed);
        $this->assertSame('realm-france-fragment', $world->armies['levy-1']->originalRealmId);
        $this->assertGreaterThan(12, $world->armies['levy-1']->corruption);
        $this->assertSame(HostKind::DEMONIC_HOST, $world->hosts['host-1']->kind);
        $this->assertNotSame($world->armies['levy-1']->id, $world->hosts['host-1']->id);
    }

    public function test_closing_a_breach_collapses_unbound_hosts_outside_strongholds(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 45;
        $world->territory('center')->incursionState = IncursionState::BREACHED;
        $world->territory('center')->corruption = 20;
        $world->territory('center')->despair = 10;
        $breach = $engine->openBreach($world, 'center');

        $host = new DemonicHost('host-bound', 'center', 'locust_host');
        $host->boundBreachId = $breach->id;
        $host->strength = 15;
        $world->hosts[$host->id] = $host;

        $attempt = new \App\Domain\Hell\CountermeasureAttempt(\App\Domain\Hell\Enums\CountermeasureKind::CLOSE_BREACH, 'center');
        $attempt->actorCharacterId = 'legate-1';
        $attempt->targetBreachId = $breach->id;
        $world->apocalypseIntensity = 12;
        $world->territory('center')->corruption = 8;
        $world->territory('center')->despair = 5;
        $attempt->factors = [
            'breach_open' => true,
            'clergy_rank_weight' => 100,
            'relic_authenticity' => 100,
            'holy_order_support' => 100,
            'military_force' => 100,
            'martyrdom_offered' => 80,
            'named_demon_rank' => 0,
            'host_strength' => 5,
            'local_corruption' => 8,
            'apocalypse_intensity' => 12,
        ];

        $outcome = $engine->attemptCountermeasure($world, $attempt);

        $this->assertSame(\App\Domain\Hell\Enums\CountermeasureResult::SUCCESS, $outcome->result);
        $this->assertFalse($world->breaches[$breach->id]->open);
        $this->assertTrue($world->hosts['host-bound']->collapsed);
    }

    public function test_pulse_action_matches_engine(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 15;
        DemonicThreatHarness::ripeForCorruption($world, 'center');
        $world->territory('center')->incursionState = IncursionState::TEMPTED;

        $result = (new PulseHellIncursion($engine))->execute($world);

        $this->assertSame(IncursionState::CORRUPTED, $result->world->territory('center')->incursionState);
        $this->assertNotEmpty($result->events);
    }

    public function test_possession_is_a_relation_and_deepens_with_corrupted_rule(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 20;
        $world->territory('center')->incursionState = IncursionState::CORRUPTED;
        $world->territory('center')->corruption = 50;
        $link = DemonicThreatHarness::withPossession($world, 'lord-center');
        $link->intensity = 70;

        $engine->pulse($world);

        $fresh = $world->possessions[$link->id];
        $this->assertSame('lord-center', $fresh->characterId);
        $this->assertSame('inhabiting_spirit', $fresh->sourceCatalogKey);
        $this->assertSame(\App\Domain\Hell\Enums\PossessionStage::OPPRESSION, $fresh->stage);
    }

    public function test_infernal_stronghold_crushes_production_and_parish_life(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 80;
        $t = $world->territory('center');
        $t->incursionState = IncursionState::INFERNAL_STRONGHOLD;
        $t->population = 2000;
        $t->clergyPresence = 12;
        $t->plague = 40;
        $beforePop = $t->population;

        $engine->pulse($world);

        $after = $world->territory('center');
        $this->assertLessThan($beforePop, $after->population);
        $this->assertSame(0.05, $after->production);
        $this->assertFalse($after->travelOpen);
        $this->assertGreaterThan(0, $after->clergyPressure);
        $this->assertGreaterThanOrEqual(0, $after->population);
    }
}
