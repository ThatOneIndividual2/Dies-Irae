<?php

namespace App\Events;

final class HolyOrderDissolved
{
    public function __construct(
        public int $holyOrderId,
        public int $actorId,
        public string $date
    ) {
    }
}
