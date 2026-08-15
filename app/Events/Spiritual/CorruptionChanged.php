<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CorruptionChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $corruptionStateId,
        public string $subjectType,
        public int $subjectId,
        public int $intensity
    ) {
    }
}
