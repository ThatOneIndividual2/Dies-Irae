<?php

namespace App\Events;

final class SpiritualOfficeAppointed
{
    public function __construct(
        public int $officeId,
        public int $holderId,
        public int $holdershipId,
        public ?int $appointedById,
        public string $date
    ) {
    }
}
