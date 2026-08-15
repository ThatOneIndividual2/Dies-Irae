<?php

namespace App\Domain\Economy;

use App\Domain\Enums\SettlementKind;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;

/**
 * Data-driven yield and disruption tables. No dice. Callers pass the local state.
 */
final class YieldCatalog
{
    public const YIELD_PER_ACRE = 60;
    public const ACRES_PER_WORKER = 6;
    public const LIVESTOCK_FODDER_PER_HEAD = 1;
    public const WORKSHOP_PER_BURGHER_BP = 2000;
    public const ARMY_RATION = 2;
    public const TITHE_BP = 1000;
    public const RENT_BP = 800;
    public const TAX_GOLD_PER_TAX_BASE = 1;
    public const NOBLE_OBLIGATION_BP = 500;
    public const BASE_PRICE = 100;
    public const WINTER_SPOIL_BP = 400;
    public const BREED_BP = 400;

    /** @var array<string,int> */
    public const ARABLE_BY_KIND = [
        SettlementKind::VILLAGE => 2800,
        SettlementKind::TOWN => 1400,
        SettlementKind::CITY => 700,
        SettlementKind::MONASTERY => 900,
        SettlementKind::PORT => 500,
        SettlementKind::CAMP => 80,
    ];

    /** @var array<string,int> */
    public const TRANSPORT_BY_KIND = [
        SettlementKind::VILLAGE => 400,
        SettlementKind::TOWN => 1200,
        SettlementKind::CITY => 2500,
        SettlementKind::MONASTERY => 600,
        SettlementKind::PORT => 3000,
        SettlementKind::CAMP => 200,
    ];

    /** @var array<string,array<string,int>> */
    public const APOCALYPSE_EFFECTS = [
        'crop_blight' => ['blight_bp' => 4500],
        'spoiled_grain' => ['spoil_stores_bp' => 2800],
        'livestock_death' => ['livestock_kill_bp' => 3500],
        'abandoned_farmland' => ['abandon_arable_bp' => 2500],
        'blocked_roads' => ['block_routes' => 1],
        'haunted_routes' => ['haunt_routes' => 1],
        'reduced_labor' => ['labor_penalty_bp' => 3000],
    ];

    public static function arableFor(string $kind): int
    {
        return self::ARABLE_BY_KIND[$kind] ?? 1000;
    }

    public static function transportFor(string $kind): int
    {
        return self::TRANSPORT_BY_KIND[$kind] ?? 500;
    }

    public static function combine(int ...$modifiers): int
    {
        $acc = BasisPoints::FULL;
        foreach ($modifiers as $bp) {
            $acc = BasisPoints::scale($acc, IntClamp::between($bp, 0, BasisPoints::FULL));
        }

        return $acc;
    }

    public static function weatherToBp(int $weather): int
    {
        return IntClamp::between($weather, 0, 15000);
    }

    /**
     * @return array<string,int>
     */
    public static function apocalypseEffect(string $key): array
    {
        return self::APOCALYPSE_EFFECTS[$key] ?? [];
    }
}
