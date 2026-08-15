<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemptationResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $temptationId,
        public int $characterId,
        public string $resolution
    ) {
    }
}
