<?php

namespace App\Events;

final class TitleRevoked
{
    public function __construct(
        public int $titleId,
        public int $previousHolderId,
        public ?int $revokedById,
        public string $date
    ) {
    }
}
