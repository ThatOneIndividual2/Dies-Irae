<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemonicInfluenceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $influenceId,
        public int $characterId,
        public string $stage
    ) {
    }
}
