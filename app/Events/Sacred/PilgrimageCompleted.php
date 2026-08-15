<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class PilgrimageCompleted
{
    use Dispatchable;

    public function __construct(
        public int $pilgrimageId,
        public int $characterId,
        public int $routeId
    ) {
    }
}
