<?php

namespace App\Actions\Population;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Population\Displacement;

final class DisplaceCohorts
{
    public function execute(
        CatastropheEngine $engine,
        string $fromId,
        ?string $toId,
        int $souls,
        string $cause = DisplacementCause::PLAGUE
    ): Displacement {
        return $engine->displace($fromId, $toId, $souls, $cause);
    }
}
