<?php

namespace App\Events;

final class HolyOrderRecognitionWithdrawn
{
    public function __construct(
        public int $holyOrderId,
        public int $actorId,
        public string $date
    ) {
    }
}
