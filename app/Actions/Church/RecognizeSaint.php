<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\Saint;
use Carbon\CarbonInterface;

final class RecognizeSaint
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, string $key, string $name, CarbonInterface $date, ?Character $subject = null): Saint
    {
        return $this->church->recognizeSaint($actor, $key, $name, $date, $subject);
    }
}
