<?php

namespace App\Domain\Economy;

use App\Domain\Support\BasisPoints;

final class DuesCollector
{
    /**
     * @param  array<string, EconomicSite>  $sites
     * @return array<string,int>
     */
    public function collect(EconomicSite $site, array $sites): array
    {
        $ruin = $site->settlement->ruinModifiers();
        $taxMult = $site->famine->taxMultiplierBp;
        $tax = BasisPoints::of($site->settlement->taxBase() * YieldCatalog::TAX_GOLD_PER_TAX_BASE, $taxMult);
        $tax = $ruin->applyTax($tax);
        $site->lastTax = $tax;
        $site->gold += $tax;

        $rent = 0;
        if ($ruin->tithe) {
            $rent = BasisPoints::of($site->civicStores(), YieldCatalog::RENT_BP);
            $rent = $site->takeCivicStores($rent);
        }
        $site->lastRent = $rent;

        $obligation = BasisPoints::of($site->gold, YieldCatalog::NOBLE_OBLIGATION_BP);
        $obligation = min($obligation, $site->gold);
        $site->gold -= $obligation;

        $tithe = 0;
        if ($ruin->tithe && $site->lastHarvest > 0) {
            $tithe = BasisPoints::of($site->lastHarvest, YieldCatalog::TITHE_BP);
            $tithe = $site->takeCivicStores($tithe);
            $house = $this->nearestMonastery($site, $sites);
            if ($house !== null) {
                $house->monasteryStores += $tithe;
            } elseif ($site->isMonastery()) {
                $site->monasteryStores += $tithe;
            } else {
                $site->addCivicStores($tithe);
                $tithe = 0;
            }
        }
        $site->lastTithe = $tithe;

        return [
            'tax' => $tax,
            'rent' => $rent,
            'tithe' => $tithe,
            'noble_obligation' => $obligation,
        ];
    }

    /**
     * @param  array<string, EconomicSite>  $sites
     */
    private function nearestMonastery(EconomicSite $from, array $sites): ?EconomicSite
    {
        if ($from->isMonastery()) {
            return $from;
        }
        foreach ($sites as $site) {
            if ($site->isMonastery() && $site->settlement->worldId === $from->settlement->worldId) {
                return $site;
            }
        }

        return null;
    }
}
