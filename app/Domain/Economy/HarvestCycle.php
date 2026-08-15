<?php

namespace App\Domain\Economy;

use App\Domain\Enums\SeasonPhase;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;

final class HarvestCycle
{
    /**
     * @return array<string,int>
     */
    public function modifiers(EconomicSite $site): array
    {
        $settlement = $site->settlement;
        $weather = YieldCatalog::weatherToBp($site->weatherBp);
        $war = BasisPoints::complement($site->warDisruptionBp);
        $plague = BasisPoints::complement(intdiv($settlement->diseaseBurden(), 2));
        $corruption = BasisPoints::complement($settlement->corruption * 80);
        $blight = BasisPoints::complement($site->blightBp);
        $laborPenalty = BasisPoints::complement($site->laborPenaltyBp);
        $ruin = $settlement->ruinModifiers()->food;
        $needed = max(1, intdiv($site->usableArable(), YieldCatalog::ACRES_PER_WORKER));
        $sick = intdiv($settlement->infectious + $settlement->incubating, 2);
        $hands = IntClamp::nonNegative($settlement->workforce() - $sick);
        $labor = IntClamp::between(intdiv($hands * 10000, $needed), 0, 10000);
        $labor = BasisPoints::scale($labor, $laborPenalty);
        $land = IntClamp::between($site->landQuality * 100, 0, 10000);

        return [
            'weather' => $weather,
            'war' => $war,
            'plague' => $plague,
            'corruption' => $corruption,
            'blight' => $blight,
            'labor' => $labor,
            'ruin' => $ruin,
            'land' => $land,
            'combined' => YieldCatalog::combine($weather, $war, $plague, $corruption, $blight, $labor, $ruin, $land),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function apply(EconomicSite $site, string $phase): array
    {
        $mods = $this->modifiers($site);

        return match ($phase) {
            SeasonPhase::PLANTING => $this->plant($site, $mods),
            SeasonPhase::GROWING => $this->grow($site, $mods),
            SeasonPhase::HARVEST => $this->harvest($site, $mods),
            default => $this->winter($site, $mods),
        };
    }

    /**
     * @param  array<string,int>  $mods
     * @return array<string,int>
     */
    private function plant(EconomicSite $site, array $mods): array
    {
        $field = YieldCatalog::combine($mods['weather'], $mods['war'], $mods['plague'], $mods['blight'], $mods['labor'], $mods['ruin']);
        $site->planted = BasisPoints::of($site->usableArable(), $field);
        $site->cropStanding = $site->planted;

        return ['planted' => $site->planted] + $mods;
    }

    /**
     * @param  array<string,int>  $mods
     * @return array<string,int>
     */
    private function grow(EconomicSite $site, array $mods): array
    {
        $health = YieldCatalog::combine($mods['weather'], $mods['blight'], $mods['corruption'], $mods['war']);
        $site->cropStanding = BasisPoints::of(max($site->planted, $site->cropStanding), $health);

        return ['crop_standing' => $site->cropStanding] + $mods;
    }

    /**
     * @param  array<string,int>  $mods
     * @return array<string,int>
     */
    private function harvest(EconomicSite $site, array $mods): array
    {
        $standing = max($site->cropStanding, $site->planted);
        $grain = intdiv($standing * YieldCatalog::YIELD_PER_ACRE * $mods['combined'], 10000);
        $site->lastHarvest = $grain;
        $site->addCivicStores($grain);
        $site->planted = 0;
        $site->cropStanding = 0;

        $breed = BasisPoints::of($site->livestock, YieldCatalog::BREED_BP);
        $breed = BasisPoints::of($breed, $mods['combined']);
        $site->livestock += $breed;

        $work = $site->workshops * 8;
        $work = BasisPoints::of($work, $site->marketActivity * 100);
        $work = $site->settlement->ruinModifiers()->applyTax($work);
        $site->gold += $work;

        return ['grain' => $grain, 'livestock_gain' => $breed, 'workshop_gold' => $work] + $mods;
    }

    /**
     * @param  array<string,int>  $mods
     * @return array<string,int>
     */
    private function winter(EconomicSite $site, array $mods): array
    {
        $spoil = YieldCatalog::WINTER_SPOIL_BP + intdiv($site->blightBp, 4) + ($site->settlement->corruption * 20);
        $lost = $site->takeCivicStores(BasisPoints::of($site->civicStores(), $spoil));
        $fodderNeed = $site->livestock * YieldCatalog::LIVESTOCK_FODDER_PER_HEAD * 20;
        $fed = min($fodderNeed, $site->civicStores());
        $site->takeCivicStores($fed);
        $unfed = IntClamp::nonNegative($fodderNeed - $fed);
        $killed = $unfed > 0 ? intdiv($site->livestock * min(6000, $unfed * 2), 10000) : 0;
        $killed += BasisPoints::of($site->livestock, intdiv($site->blightBp, 2));
        $site->livestock = IntClamp::nonNegative($site->livestock - $killed);

        return ['spoiled' => $lost, 'livestock_lost' => $killed] + $mods;
    }
}
