<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use Carbon\CarbonInterface;

final class CreateHolyOrderHeadquarters
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Holding $holding,
        CarbonInterface $date,
        ?Character $commander = null,
        string $name = 'Headquarters'
    ): HolyOrderHouse {
        return $this->orders->createHeadquarters($order, $holding, $date, $commander, $name);
    }
}
