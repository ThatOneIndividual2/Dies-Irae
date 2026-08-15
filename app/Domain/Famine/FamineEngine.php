<?php

namespace App\Domain\Famine;

use App\Domain\Economy\EconomicSite;
use App\Domain\Economy\FieldForager;
use App\Domain\Enums\FamineStage;
use App\Domain\Enums\SocialClass;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Support\IntClamp;

final class FamineEngine
{
    /** @var array<string,int> */
    public const STARVATION_WEIGHTS = [
        SocialClass::NOBLES => 15,
        SocialClass::CLERGY => 40,
        SocialClass::BURGHERS => 70,
        SocialClass::PEASANTS => 150,
        SocialClass::UNFREE => 160,
    ];

    public function __construct(private ApplyMassMortality $mortality)
    {
    }

    /**
     * @param  FieldForager[]  $armies
     */
    public function evaluate(EconomicSite $site, array $armies = []): FaminePressure
    {
        $pressure = $site->famine;
        $demand = $site->dailyDemand();
        foreach ($armies as $army) {
            if ($army->settlementId === $site->id()) {
                $demand += $army->dailyRations();
            }
        }

        $available = $site->totalFood();
        $pressure->deficit = IntClamp::nonNegative($demand - $available);
        $pressure->deficitBp = $demand > 0 ? intdiv($pressure->deficit * 10000, $demand) : 0;
        $pressure->daysOfFood = $demand > 0 ? intdiv($available, $demand) : 99;

        if ($pressure->deficit > 0) {
            $pressure->consecutiveHungryDays++;
            $pressure->malnutrition = IntClamp::between($pressure->malnutrition + 6 + intdiv($pressure->deficitBp, 800), 0, 100);
        } else {
            $pressure->recover(1);
        }

        $pressure->stage = $this->stageFrom($pressure);
        $pressure->unrest = IntClamp::between(
            intdiv($pressure->malnutrition * 6, 10) + intdiv($pressure->consecutiveHungryDays, 2) + intdiv($site->settlement->despair, 4),
            0,
            100
        );
        $pressure->crime = IntClamp::between(intdiv($pressure->unrest * 7, 10) + intdiv($pressure->malnutrition, 5), 0, 100);
        $pressure->diseaseSusceptibility = IntClamp::between($pressure->malnutrition + intdiv($pressure->consecutiveHungryDays, 2), 0, 100);
        $pressure->militaryDesertion = IntClamp::between(intdiv($pressure->malnutrition * 5, 10) + intdiv($pressure->unrest, 4), 0, 100);
        $pressure->desperation = IntClamp::between(
            $pressure->malnutrition + intdiv($site->settlement->despair, 2) + intdiv($pressure->consecutiveHungryDays, 2),
            0,
            100
        );
        $pressure->taxMultiplierBp = IntClamp::between(10000 - ($pressure->unrest * 70) - ($pressure->malnutrition * 30), 0, 10000);

        if ($pressure->desperation >= 85 && $pressure->malnutrition >= 75 && $pressure->daysOfFood === 0) {
            $pressure->extremeHook = 'cannibalism';
        }

        $site->settlement->despair = IntClamp::between(
            max($site->settlement->despair, intdiv($pressure->malnutrition, 2)),
            0,
            100
        );
        $site->settlement->morale = IntClamp::between(
            $site->settlement->morale - intdiv($pressure->malnutrition, 20),
            0,
            100
        );

        return $pressure;
    }

    public function applyStarvation(EconomicSite $site): int
    {
        $pressure = $site->famine;
        $pressure->resetDeaths();
        if ($pressure->daysOfFood > 0 || $site->settlement->souls() === 0) {
            return 0;
        }

        $rate = 80 + ($pressure->malnutrition * 12) + ($pressure->consecutiveHungryDays * 8);
        $deaths = intdiv($site->settlement->souls() * $rate, 10000);
        $deaths = min($deaths, $site->settlement->souls());
        if ($deaths <= 0) {
            return 0;
        }

        $this->mortality->apply($site->settlement, $deaths, self::STARVATION_WEIGHTS, 'famine');
        $pressure->starvationDeaths = $deaths;

        return $deaths;
    }

    public function shouldFlee(EconomicSite $site): bool
    {
        return $site->famine->isFamine()
            && $site->famine->daysOfFood <= 2
            && $site->settlement->souls() > 40;
    }

    public function fleeCount(EconomicSite $site): int
    {
        $souls = $site->settlement->souls();
        $share = 8 + intdiv($site->famine->desperation, 5);

        return min($souls - 10, max(0, intdiv($souls * $share, 100)));
    }

    private function stageFrom(FaminePressure $pressure): string
    {
        if ($pressure->daysOfFood === 0 && $pressure->malnutrition >= 70 && $pressure->unrest >= 60) {
            return FamineStage::COLLAPSE;
        }
        if ($pressure->daysOfFood === 0 && $pressure->consecutiveHungryDays >= 3) {
            return FamineStage::STARVATION;
        }
        if ($pressure->daysOfFood <= 2 || $pressure->malnutrition >= 45) {
            return FamineStage::FAMINE;
        }
        if ($pressure->daysOfFood <= 6 || $pressure->malnutrition >= 25) {
            return FamineStage::HUNGER;
        }
        if ($pressure->daysOfFood <= 13) {
            return FamineStage::SHORTAGE;
        }

        return FamineStage::NONE;
    }
}
