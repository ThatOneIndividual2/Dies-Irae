<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\ChurchFractureResponseRecord;
use App\Models\ReligiousMovement;
use Carbon\CarbonInterface;

final class IssueChurchFractureResponse
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        Character $actor,
        ReligiousMovement $movement,
        string $response,
        CarbonInterface $date,
        array $payload = []
    ): ChurchFractureResponseRecord {
        return $this->engine->churchRespond($actor, $movement, $response, $date, $payload);
    }
}
