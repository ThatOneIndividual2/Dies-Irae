<?php

namespace App\Events;

final class SaintRecognized
{
    public function __construct(
        public int $saintId,
        public int $actorId,
        public string $date
    ) {
    }
}
