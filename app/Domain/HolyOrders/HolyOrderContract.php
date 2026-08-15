<?php

namespace App\Domain\HolyOrders;

/**
 * Holy Orders are hybrid religious-military corporations.
 * They are not ordinary armies owned by the Church, and not secular titles.
 */
interface HolyOrderContract
{
    public function domainKey(): string;

    public function organization(): HolyOrderService;
}
