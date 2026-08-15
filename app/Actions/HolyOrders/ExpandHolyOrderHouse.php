<?php

namespace App\Actions\HolyOrders;

use App\Domain\Enums\HolyOrderHouseType;
use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use Carbon\CarbonInterface;

final class ExpandHolyOrderHouse
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Holding $holding,
        CarbonInterface $date,
        string $houseType = HolyOrderHouseType::COMMANDERY,
        ?Character $commander = null,
        ?string $name = null
    ): HolyOrderHouse {
        return $this->orders->expandHouse($order, $holding, $date, $houseType, $commander, $name);
    }
}
