<?php

namespace App\Domain\Famine;

use App\Domain\Enums\FamineStage;
use App\Domain\Support\IntClamp;

final class FaminePressure
{
    public string $stage = FamineStage::NONE;
    public int $deficit = 0;
    public int $deficitBp = 0;
    public int $daysOfFood = 0;
    public int $malnutrition = 0;
    public int $consecutiveHungryDays = 0;
    public int $starvationDeaths = 0;
    public int $unrest = 0;
    public int $crime = 0;
    public int $diseaseSusceptibility = 0;
    public int $militaryDesertion = 0;
    public int $taxMultiplierBp = 10000;
    public int $desperation = 0;
    public ?string $extremeHook = null;

    public function resetDeaths(): void
    {
        $this->starvationDeaths = 0;
    }

    public function isFamine(): bool
    {
        return FamineStage::weight($this->stage) >= FamineStage::weight(FamineStage::FAMINE);
    }

    public function recover(int $fedDays): void
    {
        $fedDays = max(0, $fedDays);
        $this->malnutrition = IntClamp::between($this->malnutrition - ($fedDays * 4), 0, 100);
        $this->unrest = IntClamp::between($this->unrest - ($fedDays * 3), 0, 100);
        $this->crime = IntClamp::between($this->crime - ($fedDays * 2), 0, 100);
        $this->desperation = IntClamp::between($this->desperation - ($fedDays * 4), 0, 100);
        $this->consecutiveHungryDays = 0;
        $this->taxMultiplierBp = IntClamp::between($this->taxMultiplierBp + ($fedDays * 400), 0, 10000);
        $this->militaryDesertion = IntClamp::between($this->militaryDesertion - ($fedDays * 3), 0, 100);
        $this->diseaseSusceptibility = IntClamp::between($this->diseaseSusceptibility - ($fedDays * 3), 0, 100);
        if ($this->malnutrition < 70) {
            $this->extremeHook = null;
        }
    }
}
