<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class MiracleRecognized
{
    use Dispatchable;

    public function __construct(
        public int $miracleId,
        public int $actorId
    ) {
    }
}
