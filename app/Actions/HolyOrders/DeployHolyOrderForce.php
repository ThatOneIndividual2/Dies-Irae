<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\HolyOrder;
use App\Models\HolyOrderForce;
use App\Models\HolyOrderHouse;
use Carbon\CarbonInterface;

final class DeployHolyOrderForce
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        HolyOrder $order,
        CarbonInterface $date,
        int $knights,
        int $sergeants,
        int $chaplains,
        ?string $targetKind = null,
        ?HolyOrderHouse $house = null,
        ?Character $commander = null,
        ?int $territoryId = null
    ): HolyOrderForce {
        return $this->orders->deployForce(
            $order,
            $date,
            $knights,
            $sergeants,
            $chaplains,
            $targetKind,
            $house,
            $commander,
            $territoryId
        );
    }
}
