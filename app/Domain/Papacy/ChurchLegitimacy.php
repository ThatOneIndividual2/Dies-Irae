<?php

namespace App\Domain\Papacy;

use App\Domain\Support\IntClamp;

final class ChurchLegitimacy
{
    public int $recognizedBp;
    public int $rivalBp = 0;

    public function __construct(int $recognizedBp = 8000)
    {
        $this->recognizedBp = IntClamp::between($recognizedBp, 0, 10000);
    }

    public function applyVacancyDecay(int $amount): void
    {
        $this->recognizedBp = IntClamp::between($this->recognizedBp - $amount, 0, 10000);
    }

    public function splitForSchism(int $amount): void
    {
        $moved = min($this->recognizedBp, $amount);
        $this->recognizedBp -= $moved;
        $this->rivalBp = IntClamp::between($this->rivalBp + $moved, 0, 10000);
    }

    public function restore(int $amount): void
    {
        $this->recognizedBp = IntClamp::between($this->recognizedBp + $amount, 0, 10000);
    }

    public function absorbRival(int $amount): void
    {
        $moved = min($this->rivalBp, $amount);
        $this->rivalBp -= $moved;
        $this->recognizedBp = IntClamp::between($this->recognizedBp + $moved, 0, 10000);
    }

    public function penalize(int $amount): void
    {
        $this->recognizedBp = IntClamp::between($this->recognizedBp - $amount, 0, 10000);
    }
}
