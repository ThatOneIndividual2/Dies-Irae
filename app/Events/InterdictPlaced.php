<?php

namespace App\Events;

final class InterdictPlaced
{
    public function __construct(
        public int $stateId,
        public int $actorId,
        public string $date
    ) {
    }
}
