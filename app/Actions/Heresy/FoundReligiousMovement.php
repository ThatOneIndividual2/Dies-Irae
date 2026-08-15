<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\ReligiousMovement;
use App\Models\Territory;
use Carbon\CarbonInterface;

final class FoundReligiousMovement
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        string $kind,
        string $key,
        string $name,
        Character $founder,
        Territory $origin,
        CarbonInterface $date,
        array $attributes = []
    ): ReligiousMovement {
        return $this->engine->foundMovement($kind, $key, $name, $founder, $origin, $date, $attributes);
    }
}
