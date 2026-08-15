<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use App\Models\HolyOrderMembership;
use Carbon\CarbonInterface;

final class RecruitHolyOrderMember
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        Character $character,
        string $rank,
        CarbonInterface $date,
        bool $swearRule = true,
        ?HolyOrderHouse $house = null
    ): HolyOrderMembership {
        return $this->orders->recruit($order, $character, $rank, $date, $swearRule, $house);
    }
}
