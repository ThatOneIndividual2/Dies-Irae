<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Papacy;
use Carbon\CarbonInterface;

final class DeclarePapalVacancy
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Papacy $papacy, CarbonInterface $date): Papacy
    {
        return $this->church->declarePapalVacancy($papacy, $date);
    }
}
