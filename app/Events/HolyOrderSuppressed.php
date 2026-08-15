<?php

namespace App\Events;

final class HolyOrderSuppressed
{
    public function __construct(
        public int $holyOrderId,
        public int $actorId,
        public string $date
    ) {
    }
}
