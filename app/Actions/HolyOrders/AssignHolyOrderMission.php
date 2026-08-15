<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderMission;
use Carbon\CarbonInterface;

final class AssignHolyOrderMission
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        string $missionType,
        CarbonInterface $date,
        ?Character $assignedBy = null
    ): HolyOrderMission {
        return $this->orders->assignMission($order, $missionType, $date, $assignedBy);
    }
}
