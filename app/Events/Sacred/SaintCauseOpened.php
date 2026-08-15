<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class SaintCauseOpened
{
    use Dispatchable;

    public function __construct(
        public int $saintId,
        public int $characterId
    ) {
    }
}
