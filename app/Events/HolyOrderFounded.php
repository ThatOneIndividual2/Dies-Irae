<?php

namespace App\Events;

final class HolyOrderFounded
{
    public function __construct(
        public int $holyOrderId,
        public int $founderId,
        public string $date
    ) {
    }
}
