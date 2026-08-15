<?php

namespace App\Domain\Population;

final class MortalityConsequences
{
    public string $settlementId;
    public int $soulsLost;
    /** @var array<string,int> */
    public array $deathsByClass;
    public int $workforceBefore;
    public int $workforceAfter;
    public int $taxBaseBefore;
    public int $taxBaseAfter;
    public int $levyBaseBefore;
    public int $levyBaseAfter;
    public int $armyAttrition;
    public int $successionPressure;
    public int $clergyVacancies;
    public bool $nobleExtinction;
    public bool $viabilityLost;
    public int $foodProductionBefore;
    public int $foodProductionAfter;
    public int $rebellionPressure;
    public int $migrationPressure;
    public int $unburiedCorpses;
    public int $corpsePressure;
    public int $despair;
    public int $corruption;
    public string $ruinState;
    public string $ruinBefore;

    public function __construct(string $settlementId)
    {
        $this->settlementId = $settlementId;
        $this->soulsLost = 0;
        $this->deathsByClass = [];
        $this->workforceBefore = 0;
        $this->workforceAfter = 0;
        $this->taxBaseBefore = 0;
        $this->taxBaseAfter = 0;
        $this->levyBaseBefore = 0;
        $this->levyBaseAfter = 0;
        $this->armyAttrition = 0;
        $this->successionPressure = 0;
        $this->clergyVacancies = 0;
        $this->nobleExtinction = false;
        $this->viabilityLost = false;
        $this->foodProductionBefore = 0;
        $this->foodProductionAfter = 0;
        $this->rebellionPressure = 0;
        $this->migrationPressure = 0;
        $this->unburiedCorpses = 0;
        $this->corpsePressure = 0;
        $this->despair = 0;
        $this->corruption = 0;
        $this->ruinState = '';
        $this->ruinBefore = '';
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
