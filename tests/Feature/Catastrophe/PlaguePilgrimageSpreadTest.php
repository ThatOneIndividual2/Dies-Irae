<?php

namespace Tests\Feature\Catastrophe;

use App\Domain\Enums\SettlementKind;
use App\Domain\Enums\SpreadVector;
use App\Domain\Population\PopulationCohorts;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class PlaguePilgrimageSpreadTest extends DomainTestCase
{
    public function test_plague_spreads_along_a_pilgrimage_route(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::city($engine, 'lyon', 'Lyon');
        WorldFixture::town(
            $engine,
            'le-puy',
            'Le Puy',
            SettlementKind::MONASTERY,
            new PopulationCohorts(8, 220, 40, 180, 20)
        );
        $engine->link('lyon', 'le-puy', SpreadVector::PILGRIMAGE, 7500, false);
        $engine->seedPlague('lyon', 1000);

        $engine->tick(1);

        $shrine = $engine->settlement('le-puy');
        $this->assertGreaterThan(0, $shrine->incubating + $shrine->infectious);
        $engine->assertNonNegative();
    }

    public function test_pilgrimage_spreads_harder_than_adjacency_at_the_same_traffic(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::city($engine, 'origin', 'Origin');
        WorldFixture::town($engine, 'next-door', 'Next Door');
        WorldFixture::town($engine, 'shrine', 'Shrine');
        $engine->link('origin', 'next-door', SpreadVector::ADJACENT, 7000, false);
        $engine->link('origin', 'shrine', SpreadVector::PILGRIMAGE, 7000, false);
        $engine->seedPlague('origin', 1200);
        $engine->tick(1);

        $this->assertGreaterThan(
            $engine->settlement('next-door')->incubating,
            $engine->settlement('shrine')->incubating
        );
        $this->assertGreaterThan(0, $engine->settlement('shrine')->incubating);
        $this->assertGreaterThan(0, $engine->settlement('next-door')->incubating);
    }
}
