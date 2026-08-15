<?php

namespace App\Events;

use App\Models\World;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApocalypseSignalRecorded
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, int>  $appliedDeltas
     */
    public function __construct(
        public World $world,
        public string $signalKey,
        public int $magnitude,
        public array $appliedDeltas,
        public string $worldDate
    ) {
    }
}
