<?php

namespace Tests\Feature\Catastrophe;

use App\Actions\Catastrophe\MoveArmyHost;
use App\Domain\Enums\SpreadVector;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class PlagueArmySpreadTest extends DomainTestCase
{
    public function test_an_infected_army_seeds_the_settlement_it_occupies(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::city($engine, 'marseille', 'Marseille');
        WorldFixture::town($engine, 'arles', 'Arles');
        $engine->addArmy('host-of-provence', 800, 'marseille', 400);

        $this->assertSame(0, $engine->settlement('marseille')->incubating);

        $engine->tick(1);

        $here = $engine->settlement('marseille');
        $this->assertGreaterThan(0, $here->incubating + $here->infectious);
        $engine->assertNonNegative();
    }

    public function test_a_marching_army_carries_plague_into_the_next_town(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::city($engine, 'marseille', 'Marseille');
        WorldFixture::town($engine, 'arles', 'Arles');
        $engine->addArmy('host-of-provence', 800, 'marseille', 400);

        $this->assertSame(0, $engine->settlement('arles')->incubating);
        $this->assertSame(0, $engine->settlement('arles')->infectious);

        (new MoveArmyHost())->execute($engine, 'host-of-provence', 'arles');

        $arles = $engine->settlement('arles');
        $this->assertGreaterThan(0, $arles->incubating + $arles->infectious);
        $this->assertSame('arles', $engine->hosts['host-of-provence']->locationSettlementId);

        $armyVectors = [];
        foreach ($engine->links as $link) {
            if ($link->vector === SpreadVector::ARMY) {
                $armyVectors[] = $link;
            }
        }
        $this->assertNotEmpty($armyVectors);
    }

    public function test_civic_quarantine_barely_stops_an_army(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::town($engine, 'clean', 'Clean Gate');
        $engine->settlement('clean')->quarantine = 'cordon';
        $engine->addArmy('plague-host', 600, 'clean', 300);

        $engine->tick(1);

        $this->assertGreaterThan(0, $engine->settlement('clean')->incubating + $engine->settlement('clean')->infectious);
    }
}
