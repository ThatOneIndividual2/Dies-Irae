<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class RelicDestroyed
{
    use Dispatchable;

    public function __construct(
        public int $relicId,
        public int $actorId
    ) {
    }
}
