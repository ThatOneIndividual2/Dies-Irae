<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class MiracleDisputed
{
    use Dispatchable;

    public function __construct(
        public int $miracleId
    ) {
    }
}
