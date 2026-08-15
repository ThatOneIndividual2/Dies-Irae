<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use App\Models\Relic;
use App\Models\RelicCustody;
use Carbon\CarbonInterface;

final class EntrustRelicToHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Relic $relic,
        CarbonInterface $date,
        ?HolyOrderHouse $house = null,
        ?Character $actor = null
    ): RelicCustody {
        return $this->orders->entrustRelic($order, $relic, $date, $house, $actor);
    }
}
