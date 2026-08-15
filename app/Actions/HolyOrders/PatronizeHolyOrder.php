<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderPatronage;
use Carbon\CarbonInterface;

final class PatronizeHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Character $patron,
        CarbonInterface $date,
        ?int $realmId = null,
        ?int $titleId = null
    ): HolyOrderPatronage {
        return $this->orders->patronize($order, $patron, $date, $realmId, $titleId);
    }
}
