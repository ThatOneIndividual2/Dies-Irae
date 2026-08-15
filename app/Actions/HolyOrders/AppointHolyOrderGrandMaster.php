<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderMembership;
use Carbon\CarbonInterface;

final class AppointHolyOrderGrandMaster
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Character $character,
        CarbonInterface $date,
        ?Character $appointedBy = null
    ): HolyOrderMembership {
        return $this->orders->appointGrandMaster($order, $character, $date, $appointedBy);
    }
}
