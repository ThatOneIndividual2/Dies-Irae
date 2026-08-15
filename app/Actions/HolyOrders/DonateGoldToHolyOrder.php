<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderTreasuryEntry;
use Carbon\CarbonInterface;

final class DonateGoldToHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Character $donor,
        int $amount,
        CarbonInterface $date,
        ?int $sourceRealmId = null
    ): HolyOrderTreasuryEntry {
        return $this->orders->donateGold($order, $donor, $amount, $date, $sourceRealmId);
    }
}
