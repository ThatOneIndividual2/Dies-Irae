<?php

namespace Tests\Feature\Catastrophe;

use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\SocialClass;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Population\Ports\NullCharacterDeathPort;
use App\Domain\Population\Ports\RecordingClergyVacancyPort;
use App\Domain\Population\Ports\RecordingNobleExtinctionPort;
use App\Domain\Population\Ports\RecordingSuccessionPressurePort;
use App\Validation\PopulationBoundsValidator;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class MassMortalityEffectsTest extends DomainTestCase
{
    public function test_mass_death_hits_workforce_tax_levy_food_and_despair(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::city($engine, 'aix', 'Aix');
        $beforeWorkforce = $town->workforce();
        $beforeTax = $town->taxBase();
        $beforeLevy = $town->levyBase();
        $beforeFood = $town->foodProduction();

        $clergy = new RecordingClergyVacancyPort();
        $succession = new RecordingSuccessionPressurePort();
        $nobles = new RecordingNobleExtinctionPort();
        $apply = new ApplyMassMortality(null, new NullCharacterDeathPort(), $succession, $clergy, null, $nobles);

        $result = $apply->apply($town, 6000, [
            SocialClass::NOBLES => 70,
            SocialClass::CLERGY => 145,
            SocialClass::BURGHERS => 110,
            SocialClass::PEASANTS => 100,
            SocialClass::UNFREE => 105,
        ], 'plague');

        $this->assertSame(6000, $result->soulsLost);
        $this->assertLessThan($beforeWorkforce, $result->workforceAfter);
        $this->assertLessThan($beforeTax, $result->taxBaseAfter);
        $this->assertLessThan($beforeLevy, $result->levyBaseAfter);
        $this->assertLessThan($beforeFood, $result->foodProductionAfter);
        $this->assertGreaterThan(0, $result->clergyVacancies);
        $this->assertGreaterThan(0, $result->armyAttrition);
        $this->assertGreaterThan(0, $result->unburiedCorpses);
        $this->assertGreaterThan(0, $result->corpsePressure);
        $this->assertGreaterThan(5, $result->despair);
        $this->assertGreaterThan(0, $result->migrationPressure);
        $this->assertGreaterThan(0, $result->rebellionPressure);
        $this->assertNotSame('functioning', $result->ruinState);
        $this->assertNotEmpty($clergy->events);
        $this->assertSame(0, (new NullCharacterDeathPort())->considerDeaths(1, 'aix', []));
        $engine->assertNonNegative();
    }

    public function test_a_wave_can_empty_a_town_without_killing_a_named_character(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::town($engine, 'salon', 'Salon');
        $souls = $town->souls();
        $apply = new ApplyMassMortality(null, new NullCharacterDeathPort());
        $result = $apply->apply($town, $souls, [
            SocialClass::NOBLES => 100,
            SocialClass::CLERGY => 100,
            SocialClass::BURGHERS => 100,
            SocialClass::PEASANTS => 100,
            SocialClass::UNFREE => 100,
        ], 'plague');

        $this->assertSame(0, $town->souls());
        $this->assertTrue($result->nobleExtinction);
        $this->assertTrue($result->viabilityLost);
        $this->assertGreaterThan(0, $result->clergyVacancies);
        $this->assertSame(0, $engine->characters->considerDeaths(1, 'salon', ['named_required' => false]));
        $this->assertSame([], (new PopulationBoundsValidator())->validate($engine));
    }

    public function test_local_noble_extinction_and_succession_pressure_are_recorded(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::town($engine, 'tiny-court', 'Tiny Court');
        $apply = new ApplyMassMortality(
            null,
            new NullCharacterDeathPort(),
            $engine->succession,
            $engine->clergyVacancies,
            $engine->armyAttrition,
            $engine->nobleExtinctions
        );
        $apply->apply($town, $town->souls(), [
            SocialClass::NOBLES => 1000,
            SocialClass::CLERGY => 100,
            SocialClass::BURGHERS => 100,
            SocialClass::PEASANTS => 100,
            SocialClass::UNFREE => 100,
        ], 'plague');

        $this->assertSame(0, $town->cohorts->nobles);
        $this->assertNotEmpty($engine->nobleExtinctions->events);
        $this->assertNotEmpty($engine->succession->events);
        $this->assertGreaterThan(0, $engine->succession->events[0]['pressure']);
    }

    public function test_abandoned_corpses_raise_corruption_and_despair(): void
    {
        $engine = WorldFixture::engine();
        $clean = WorldFixture::town($engine, 'clean', 'Clean');
        $filth = WorldFixture::town($engine, 'filth', 'Filth');
        $filth->corpseHandling = CorpseHandling::ABANDONED;

        $apply = new ApplyMassMortality();
        $cleanResult = $apply->apply($clean, 400, [SocialClass::PEASANTS => 100]);
        $filthResult = $apply->apply($filth, 400, [SocialClass::PEASANTS => 100]);

        $this->assertGreaterThan($cleanResult->despair, $filthResult->despair);
        $this->assertGreaterThan($cleanResult->corruption, $filthResult->corruption);
        $this->assertGreaterThan($cleanResult->unburiedCorpses, $filthResult->unburiedCorpses);
    }

    public function test_supernatural_amplification_kills_more_than_the_natural_strain(): void
    {
        $natural = WorldFixture::engine();
        WorldFixture::city($natural, 'aix', 'Aix');
        $natural->seedPlague('aix', 2000);

        $hellish = WorldFixture::engine();
        WorldFixture::city($hellish, 'aix', 'Aix');
        $hellish->profile = $hellish->profile->withSupernaturalAmplification(22000);
        $hellish->setLocalSupernatural('aix', 18000);
        $hellish->seedPlague('aix', 2000);

        $natural->tick(12);
        $hellish->tick(12);

        $this->assertLessThan(
            $natural->settlement('aix')->souls(),
            $hellish->settlement('aix')->souls()
        );
    }
}
