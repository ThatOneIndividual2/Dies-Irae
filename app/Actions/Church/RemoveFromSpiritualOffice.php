<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use Carbon\CarbonInterface;

final class RemoveFromSpiritualOffice
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, SpiritualOffice $office, CarbonInterface $date): ?SpiritualOfficeHoldership
    {
        return $this->church->removeClergy($actor, $office, $date);
    }
}
