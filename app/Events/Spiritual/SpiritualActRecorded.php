<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpiritualActRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $actRecordId,
        public int $characterId,
        public string $actType
    ) {
    }
}
