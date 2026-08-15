<?php

namespace App\Domain\Economy;

use App\Domain\Enums\SettlementKind;
use App\Domain\Famine\FaminePressure;
use App\Domain\Population\Settlement;
use App\Domain\Support\IntClamp;

final class EconomicSite
{
    public Settlement $settlement;
    public int $landQuality;
    public int $arable;
    public int $planted = 0;
    public int $cropStanding = 0;
    public int $livestock;
    public int $workshops;
    public int $marketActivity;
    public int $gold = 0;
    public int $monasteryStores = 0;
    public int $churchLegitimacy = 50;
    public bool $monasteryOverwhelmed = false;
    public int $transportCapacity;
    public int $weatherBp = 10000;
    public int $warDisruptionBp = 0;
    public int $blightBp = 0;
    public int $laborPenaltyBp = 0;
    public int $abandonedLand = 0;
    public int $regionalPrice = YieldCatalog::BASE_PRICE;
    public int $lastHarvest = 0;
    public int $lastTithe = 0;
    public int $lastTax = 0;
    public int $lastRent = 0;
    public FaminePressure $famine;

    public function __construct(Settlement $settlement)
    {
        $this->settlement = $settlement;
        $this->landQuality = 70;
        $this->arable = YieldCatalog::arableFor($settlement->kind);
        $this->livestock = max(10, intdiv($settlement->cohorts->peasants, 18));
        $this->workshops = max(0, intdiv($settlement->cohorts->burghers, 12));
        $this->marketActivity = $settlement->kind === SettlementKind::VILLAGE ? 35 : 55;
        $this->transportCapacity = YieldCatalog::transportFor($settlement->kind);
        $this->famine = new FaminePressure();
        if ($this->isMonastery()) {
            $this->monasteryStores = max(80, intdiv($settlement->foodStores, 3));
            $this->churchLegitimacy = 60;
        }
    }

    public function id(): string
    {
        return $this->settlement->id;
    }

    public function isMonastery(): bool
    {
        return $this->settlement->kind === SettlementKind::MONASTERY;
    }

    public function usableArable(): int
    {
        return IntClamp::nonNegative($this->arable - $this->abandonedLand);
    }

    public function civicStores(): int
    {
        return $this->settlement->foodStores;
    }

    public function addCivicStores(int $amount): void
    {
        $this->settlement->foodStores = IntClamp::nonNegative($this->settlement->foodStores + $amount);
    }

    public function takeCivicStores(int $amount): int
    {
        $taken = min(max(0, $amount), $this->settlement->foodStores);
        $this->settlement->foodStores -= $taken;

        return $taken;
    }

    public function takeMonasteryStores(int $amount): int
    {
        $taken = min(max(0, $amount), $this->monasteryStores);
        $this->monasteryStores -= $taken;

        return $taken;
    }

    public function totalFood(): int
    {
        return $this->settlement->foodStores + $this->monasteryStores;
    }

    public function dailyDemand(): int
    {
        return $this->settlement->foodDemand();
    }
}
