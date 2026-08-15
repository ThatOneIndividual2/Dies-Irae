<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScandalBroke
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $scandalId,
        public int $characterId,
        public string $facet
    ) {
    }
}
