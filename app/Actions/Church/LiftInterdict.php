<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\InterdictState;
use Carbon\CarbonInterface;

final class LiftInterdict
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, InterdictState $state, CarbonInterface $date): InterdictState
    {
        return $this->church->liftInterdict($actor, $state, $date);
    }
}
