<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\HolyOrder;

final class CorruptHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(HolyOrder $order, int $amount): HolyOrder
    {
        return $this->orders->corrupt($order, $amount);
    }
}
