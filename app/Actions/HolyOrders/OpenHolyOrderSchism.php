<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderSchism;
use Carbon\CarbonInterface;

final class OpenHolyOrderSchism
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        CarbonInterface $date,
        ?Character $rival = null,
        string $cause = 'internal_schism'
    ): HolyOrderSchism {
        return $this->orders->openSchism($order, $date, $rival, $cause);
    }
}
