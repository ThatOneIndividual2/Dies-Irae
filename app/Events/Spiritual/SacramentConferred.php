<?php

namespace App\Events\Spiritual;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SacramentConferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $sacramentRecordId,
        public int $subjectCharacterId,
        public string $sacramentType,
        public string $validity
    ) {
    }
}
