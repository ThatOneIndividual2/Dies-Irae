<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CanonicalStandingChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $characterId,
        public string $censure,
        public string $eucharistStanding
    ) {
    }
}
