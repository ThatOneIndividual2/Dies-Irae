<?php

namespace Tests\Feature\Economy;

use App\Actions\Economy\MoveGrain;
use App\Actions\Economy\TickHarvestSeason;
use App\Actions\Famine\TickFamine;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Enums\FamineStage;
use App\Domain\Enums\GrainMoveMode;
use App\Domain\Enums\SeasonPhase;
use Tests\DomainTestCase;
use Tests\Support\EconomyFixture;

class HarvestAndFamineTest extends DomainTestCase
{
    public function test_normal_harvest_fills_stores(): void
    {
        $engine = EconomyFixture::engine();
        EconomyFixture::village($engine);
        $site = $engine->site('cressy');
        $site->settlement->foodStores = 2000;
        $before = $site->civicStores();

        (new TickHarvestSeason())->execute($engine, SeasonPhase::PLANTING);
        (new TickHarvestSeason())->execute($engine, SeasonPhase::GROWING);
        $result = (new TickHarvestSeason())->execute($engine, SeasonPhase::HARVEST);

        $this->assertGreaterThan(0, $site->planted + $site->lastHarvest);
        $this->assertGreaterThan(20000, $site->lastHarvest);
        $this->assertGreaterThan($before, $site->civicStores());
        $this->assertSame($site->lastHarvest, $result['results']['cressy']['grain']);
        $this->assertGreaterThan(0, $result['results']['cressy']['dues']['tax']);
        $engine->assertNonNegative();
    }

    public function test_failed_harvest_is_much_smaller_than_a_fair_year(): void
    {
        $fair = EconomyFixture::engine();
        EconomyFixture::village($fair);
        $fair->site('cressy')->settlement->foodStores = 0;
        $fair->runPhase(SeasonPhase::PLANTING);
        $fair->runPhase(SeasonPhase::GROWING);
        $fair->runPhase(SeasonPhase::HARVEST);

        $failed = EconomyFixture::engine();
        EconomyFixture::village($failed);
        $failed->site('cressy')->settlement->foodStores = 0;
        $failed->site('cressy')->weatherBp = 3500;
        $failed->site('cressy')->blightBp = 8500;
        $failed->runPhase(SeasonPhase::PLANTING);
        $failed->runPhase(SeasonPhase::GROWING);
        $failed->runPhase(SeasonPhase::HARVEST);

        $this->assertGreaterThan($failed->site('cressy')->lastHarvest * 3, $fair->site('cressy')->lastHarvest);
        $this->assertLessThan(15000, $failed->site('cressy')->lastHarvest);
    }

    public function test_plague_reduces_field_labor_and_harvest(): void
    {
        $healthy = EconomyFixture::engine();
        EconomyFixture::village($healthy);
        $healthy->site('cressy')->settlement->foodStores = 0;
        $healthy->runPhase(SeasonPhase::PLANTING);
        $healthy->runPhase(SeasonPhase::GROWING);
        $healthy->runPhase(SeasonPhase::HARVEST);

        $sick = EconomyFixture::engine();
        EconomyFixture::village($sick);
        $sick->site('cressy')->settlement->foodStores = 0;
        $sick->site('cressy')->settlement->infectious = 700;
        $sick->site('cressy')->settlement->incubating = 200;
        $sick->runPhase(SeasonPhase::PLANTING);
        $sick->runPhase(SeasonPhase::GROWING);
        $sick->runPhase(SeasonPhase::HARVEST);

        $this->assertLessThan($healthy->site('cressy')->lastHarvest, $sick->site('cressy')->lastHarvest);
    }

    public function test_army_consumes_regional_food(): void
    {
        $engine = EconomyFixture::engine();
        EconomyFixture::village($engine);
        $site = $engine->site('cressy');
        $site->settlement->foodStores = 40000;
        $engine->stationArmy('host', 'cressy', 500);
        $before = $site->civicStores();
        $civilian = $site->dailyDemand();

        (new TickFamine())->execute($engine, 5);

        $expected = ($civilian + 500 * 2) * 5;
        $this->assertSame($before - $expected, $site->civicStores());
        $this->assertSame(0, $engine->armies['host']->deserted);
    }

    public function test_monastery_relief_moves_grain_and_raises_legitimacy(): void
    {
        $engine = EconomyFixture::pair();
        $village = $engine->site('cressy');
        $house = $engine->site('amand');
        $village->settlement->foodStores = 0;
        $house->monasteryStores = 9000;
        $house->settlement->foodStores = 2000;
        $house->transportCapacity = 5000;
        $village->transportCapacity = 5000;
        $engine->lanes[0]->capacity = 8000;
        $beforeLegitimacy = $house->churchLegitimacy;

        $move = (new MoveGrain())->execute($engine, 'amand', 'cressy', 4000, GrainMoveMode::ALMS, 'abbey');

        $this->assertSame('moved', $move->status);
        $this->assertSame(4000, $move->amount);
        $this->assertSame(4000, $village->civicStores());
        $this->assertSame(5000, $house->monasteryStores);
        $this->assertGreaterThan($beforeLegitimacy, $house->churchLegitimacy);
        $this->assertGreaterThan(0, $village->settlement->morale);
    }

    public function test_cascading_famine_emerges_from_empty_stores(): void
    {
        $engine = EconomyFixture::engine();
        EconomyFixture::village($engine);
        $site = $engine->site('cressy');
        $site->settlement->foodStores = 0;
        $site->monasteryStores = 0;
        $souls = $site->settlement->souls();

        (new TickFamine())->execute($engine, 8);

        $this->assertGreaterThanOrEqual(FamineStage::weight(FamineStage::FAMINE), FamineStage::weight($site->famine->stage));
        $this->assertGreaterThan(0, $site->famine->starvationDeaths);
        $this->assertLessThan($souls, $site->settlement->souls());
        $this->assertLessThan(10000, $site->famine->taxMultiplierBp);
        $this->assertGreaterThan(20, $site->famine->unrest);
        $this->assertGreaterThan(20, $site->famine->malnutrition);
        $engine->assertNonNegative();
    }

    public function test_famine_drives_migration_to_a_fed_settlement(): void
    {
        $engine = EconomyFixture::engine();
        EconomyFixture::village($engine, 'cressy');
        EconomyFixture::town($engine, 'lys');
        $engine->link('cressy', 'lys', 3000);
        $engine->site('cressy')->settlement->foodStores = 0;
        $engine->site('cressy')->monasteryStores = 0;
        $engine->site('lys')->settlement->foodStores = 200000;
        $cressyBefore = $engine->settlement('cressy')->souls();
        $lysBefore = $engine->settlement('lys')->souls();

        (new TickFamine())->execute($engine, 6);

        $this->assertNotEmpty($engine->displacements);
        $this->assertSame(DisplacementCause::FAMINE, $engine->displacements[0]->cause);
        $this->assertLessThan($cressyBefore, $engine->settlement('cressy')->souls());
        $this->assertGreaterThan($lysBefore, $engine->settlement('lys')->souls());
        $engine->assertNonNegative();
    }

    public function test_economic_recovery_after_relief_and_a_fair_harvest(): void
    {
        $engine = EconomyFixture::engine();
        EconomyFixture::village($engine);
        $site = $engine->site('cressy');
        $site->settlement->foodStores = 0;
        $site->monasteryStores = 0;

        (new TickFamine())->execute($engine, 5);
        $peakMalnutrition = $site->famine->malnutrition;
        $peakUnrest = $site->famine->unrest;
        $this->assertGreaterThan(10, $peakMalnutrition);

        $site->addCivicStores(250000);
        (new TickFamine())->execute($engine, 6);
        $engine->runPhase(SeasonPhase::PLANTING);
        $engine->runPhase(SeasonPhase::GROWING);
        $engine->runPhase(SeasonPhase::HARVEST);
        (new TickFamine())->execute($engine, 3);

        $this->assertLessThan($peakMalnutrition, $site->famine->malnutrition);
        $this->assertLessThan($peakUnrest, $site->famine->unrest);
        $this->assertGreaterThan(4000, $site->famine->taxMultiplierBp);
        $this->assertNull($site->famine->extremeHook);
        $engine->assertNonNegative();
    }

    public function test_blocked_roads_stop_grain_and_blight_is_catalogued(): void
    {
        $engine = EconomyFixture::pair();
        $engine->site('amand')->monasteryStores = 5000;
        $engine->applyApocalypse('cressy', 'blocked_roads');
        $engine->applyApocalypse('cressy', 'crop_blight');

        $move = $engine->moveGrain('amand', 'cressy', 1000, GrainMoveMode::RELIEF, 'abbey');

        $this->assertSame('blocked', $move->status);
        $this->assertSame(0, $move->amount);
        $this->assertGreaterThan(0, $engine->site('cressy')->blightBp);
    }
}
