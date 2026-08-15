<?php

namespace Tests\Support;

use App\Domain\Economy\EconomyEngine;
use App\Domain\Enums\SettlementKind;
use App\Domain\Population\PopulationCohorts;
use App\Domain\Population\Settlement;

final class EconomyFixture
{
    public static function engine(int $worldId = 1, int $month = 9): EconomyEngine
    {
        return new EconomyEngine($worldId, 1348, $month);
    }

    public static function village(EconomyEngine $engine, string $id = 'cressy', string $name = 'Cressy'): Settlement
    {
        $cohorts = new PopulationCohorts(8, 20, 60, 900, 80);

        return $engine->addSettlement(Settlement::found($engine->worldId, $id, $name, SettlementKind::VILLAGE, $cohorts))->settlement;
    }

    public static function town(EconomyEngine $engine, string $id = 'lys', string $name = 'Lys'): Settlement
    {
        $cohorts = new PopulationCohorts(40, 80, 400, 2400, 200);

        return $engine->addSettlement(Settlement::found($engine->worldId, $id, $name, SettlementKind::TOWN, $cohorts))->settlement;
    }

    public static function monastery(EconomyEngine $engine, string $id = 'amand', string $name = 'St Amand'): Settlement
    {
        $cohorts = new PopulationCohorts(0, 40, 10, 80, 20);

        return $engine->addSettlement(Settlement::found($engine->worldId, $id, $name, SettlementKind::MONASTERY, $cohorts))->settlement;
    }

    public static function pair(): EconomyEngine
    {
        $engine = self::engine();
        self::village($engine, 'cressy', 'Cressy');
        self::monastery($engine, 'amand', 'St Amand');
        $engine->link('cressy', 'amand', 2000);

        return $engine;
    }
}
