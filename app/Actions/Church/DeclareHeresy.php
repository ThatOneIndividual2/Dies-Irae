<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\Heresy;
use Carbon\CarbonInterface;

final class DeclareHeresy
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, string $key, string $name, CarbonInterface $date): Heresy
    {
        return $this->church->declareHeresy($actor, $key, $name, $date);
    }
}
