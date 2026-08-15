<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class RelicStolen
{
    use Dispatchable;

    public function __construct(
        public int $relicId,
        public int $actorId
    ) {
    }
}
