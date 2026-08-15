<?php

namespace App\Events;

final class HolyOrderSanctioned
{
    public function __construct(
        public int $holyOrderId,
        public int $actorId,
        public string $date
    ) {
    }
}
