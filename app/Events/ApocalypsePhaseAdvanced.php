<?php

namespace App\Events;

use App\Models\World;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApocalypsePhaseAdvanced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public World $world,
        public string $fromPhase,
        public string $toPhase,
        public string $worldDate
    ) {
    }
}
