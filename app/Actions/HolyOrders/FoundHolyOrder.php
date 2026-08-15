<?php

namespace App\Actions\HolyOrders;

use App\Domain\HolyOrders\HolyOrderService;
use App\Models\Character;
use App\Models\Faith;
use App\Models\HolyOrder;
use App\Models\World;
use Carbon\CarbonInterface;

final class FoundHolyOrder
{
    public function __construct(private HolyOrderService $orders)
    {
    }

    public function execute(
        World $world,
        Faith $faith,
        Character $founder,
        string $key,
        string $name,
        CarbonInterface $date,
        array $attributes = []
    ): HolyOrder {
        return $this->orders->found($world, $faith, $founder, $key, $name, $date, $attributes);
    }
}
