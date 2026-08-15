<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\InterdictState;
use App\Models\See;
use App\Models\Territory;
use Carbon\CarbonInterface;

final class PlaceInterdict
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $actor,
        CarbonInterface $date,
        ?See $see = null,
        ?Territory $territory = null,
        ?string $reason = null
    ): InterdictState {
        return $this->church->placeInterdict($actor, $date, $see, $territory, $reason);
    }
}
