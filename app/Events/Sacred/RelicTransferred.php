<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class RelicTransferred
{
    use Dispatchable;

    public function __construct(
        public int $relicId,
        public string $custodianType,
        public ?int $custodianId
    ) {
    }
}
