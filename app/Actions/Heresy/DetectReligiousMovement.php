<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\FractureDetection;
use App\Models\ReligiousMovement;
use Carbon\CarbonInterface;

final class DetectReligiousMovement
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        ReligiousMovement $movement,
        string $source,
        Character $reporter,
        CarbonInterface $date,
        array $attributes = []
    ): FractureDetection {
        return $this->engine->detect($movement, $source, $reporter, $date, $attributes);
    }
}
