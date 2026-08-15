<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\HolyOrder;
use Carbon\CarbonInterface;

final class SuppressHolyOrder
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(Character $actor, HolyOrder $order, CarbonInterface $date): HolyOrder
    {
        return $this->church->suppressHolyOrder($actor, $order, $date);
    }
}
