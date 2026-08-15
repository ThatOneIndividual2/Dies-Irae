<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PenanceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $penanceId,
        public int $characterId,
        public string $status
    ) {
    }
}
