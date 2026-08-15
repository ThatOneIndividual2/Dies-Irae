<?php

namespace App\Events;

final class RelicAuthenticityRecognized
{
    public function __construct(
        public int $relicId,
        public string $authenticity,
        public int $actorId,
        public string $date
    ) {
    }
}
