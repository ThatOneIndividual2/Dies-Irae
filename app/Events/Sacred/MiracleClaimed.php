<?php

namespace App\Events\Sacred;

use Illuminate\Foundation\Events\Dispatchable;

class MiracleClaimed
{
    use Dispatchable;

    public function __construct(
        public int $miracleId,
        public string $category
    ) {
    }
}
