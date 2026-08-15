<?php

namespace App\Events;

final class SpiritualOfficeRemoved
{
    public function __construct(
        public int $officeId,
        public int $previousHolderId,
        public ?int $removedById,
        public string $date
    ) {
    }
}
