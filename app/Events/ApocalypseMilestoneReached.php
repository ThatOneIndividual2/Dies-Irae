<?php

namespace App\Events;

use App\Models\World;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApocalypseMilestoneReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public World $world,
        public string $milestoneKey,
        public string $worldDate
    ) {
    }
}
