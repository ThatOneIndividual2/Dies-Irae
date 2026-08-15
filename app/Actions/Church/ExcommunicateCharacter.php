<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\ExcommunicationState;
use App\Models\See;
use Carbon\CarbonInterface;

final class ExcommunicateCharacter
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $actor,
        Character $target,
        CarbonInterface $date,
        ?string $reason = null,
        ?See $scopeSee = null
    ): ExcommunicationState {
        return $this->church->excommunicate($actor, $target, $date, $reason, $scopeSee);
    }
}
