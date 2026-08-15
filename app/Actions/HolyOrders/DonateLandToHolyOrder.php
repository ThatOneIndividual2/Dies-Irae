<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderHolding;
use App\Models\Title;
use Carbon\CarbonInterface;

final class DonateLandToHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Holding $holding,
        Character $donor,
        CarbonInterface $date,
        ?Title $donorTitle = null,
        bool $foundCommandery = true
    ): HolyOrderHolding {
        return $this->orders->donateLand($order, $holding, $donor, $date, $donorTitle, $foundCommandery);
    }
}
