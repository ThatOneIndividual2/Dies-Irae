<?php

namespace App\Domain\HolyOrders;

final class HolyOrderOrganization implements HolyOrderContract
{
    public function __construct(private HolyOrderService $organization)
    {
    }

    public function domainKey(): string
    {
        return 'holy_orders';
    }

    public function organization(): HolyOrderService
    {
        return $this->organization;
    }
}
