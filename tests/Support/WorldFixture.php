<?php

namespace Tests\Support;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Catastrophe\PlagueProfile;
use App\Domain\Enums\SettlementKind;
use App\Domain\Enums\SpreadVector;
use App\Domain\Population\PopulationCohorts;
use App\Domain\Population\Settlement;

final class WorldFixture
{
    public static function engine(?PlagueProfile $profile = null, int $worldId = 1): CatastropheEngine
    {
        return new CatastropheEngine($worldId, 'test-seed-'.$worldId, $profile ?? PlagueProfile::blackDeath());
    }

    public static function town(
        CatastropheEngine $engine,
        string $id,
        string $name,
        string $kind = SettlementKind::TOWN,
        ?PopulationCohorts $cohorts = null
    ): Settlement {
        $cohorts = $cohorts ?? new PopulationCohorts(40, 80, 400, 2400, 200);

        return $engine->addSettlement(Settlement::found($engine->worldId, $id, $name, $kind, $cohorts));
    }

    public static function city(CatastropheEngine $engine, string $id, string $name): Settlement
    {
        return self::town(
            $engine,
            $id,
            $name,
            SettlementKind::CITY,
            new PopulationCohorts(120, 350, 2800, 9000, 800)
        );
    }

    public static function adjacentPair(): CatastropheEngine
    {
        $engine = self::engine();
        self::city($engine, 'aix', 'Aix');
        self::town($engine, 'salon', 'Salon');
        $engine->link('aix', 'salon', SpreadVector::ADJACENT, 8000);

        return $engine;
    }
}
