<?php

namespace App\Events;

final class ExcommunicationLifted
{
    public function __construct(
        public int $characterId,
        public int $stateId,
        public string $date
    ) {
    }
}
