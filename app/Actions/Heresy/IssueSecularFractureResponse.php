<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\ReligiousMovement;
use App\Models\SecularFractureResponseRecord;
use Carbon\CarbonInterface;

final class IssueSecularFractureResponse
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
    ): SecularFractureResponseRecord {
        return $this->engine->secularRespond($actor, $movement, $response, $date, $payload);
    }
}
