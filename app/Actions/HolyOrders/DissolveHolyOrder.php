<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use Carbon\CarbonInterface;

final class DissolveHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Character $actor,
        CarbonInterface $date,
        ?Character $confiscator = null
    ): HolyOrder {
        return $this->orders->dissolve($order, $actor, $date, $confiscator);
    }
}
