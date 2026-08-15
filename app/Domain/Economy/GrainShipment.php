<?php

namespace App\Domain\Economy;

final class GrainShipment
{
    public function __construct(
        public string $id,
        public string $fromId,
        public string $toId,
        public int $amount,
        public string $mode,
        public string $actor,
        public int $goldPaid = 0,
        public string $status = 'moved'
    ) {
    }
}
