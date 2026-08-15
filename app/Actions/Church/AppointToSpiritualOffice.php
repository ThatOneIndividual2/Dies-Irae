<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\ClergyAppointment;
use App\Models\See;
use App\Models\SpiritualOffice;
use Carbon\CarbonInterface;

final class AppointToSpiritualOffice
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $actor,
        SpiritualOffice $office,
        Character $appointee,
        CarbonInterface $date,
        ?string $mode = null
    ): ClergyAppointment {
        return $this->church->appointClergy($actor, $office, $appointee, $date, $mode);
    }
}
