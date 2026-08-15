<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\HolyOrder;
use Carbon\CarbonInterface;

final class ExcommunicateHolyOrder
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, HolyOrder $order, CarbonInterface $date): HolyOrder
    {
        return $this->church->excommunicateHolyOrder($actor, $order, $date);
    }
}
