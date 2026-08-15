<?php

namespace App\Events;

final class HeresyDeclared
{
    public function __construct(
        public int $heresyId,
        public int $actorId,
        public string $date
    ) {
    }
}
