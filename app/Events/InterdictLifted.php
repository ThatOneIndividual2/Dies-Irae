<?php

namespace App\Events;

final class InterdictLifted
{
    public function __construct(
        public int $stateId,
        public string $date
    ) {
    }
}
