<?php

namespace App\Events;

final class TitleTransferred
{
    public function __construct(
        public int $titleId,
        public int $holderId,
        public int $ownershipId,
        public ?int $grantedById,
        public ?int $previousId,
        public string $date
    ) {
    }
}
