<?php

namespace App\Domain\Hell;

use App\Domain\Hell\State\ThreatEvent;
use App\Domain\Hell\State\ThreatWorld;

final class PulseResult
{
    public ThreatWorld $world;
    /** @var list<ThreatEvent> */
    public array $events = [];
    /** @var array<string, string> territoryId => new state */
    public array $transitions = [];

    public function __construct(ThreatWorld $world, array $events = [], array $transitions = [])
    {
        $this->world = $world;
        $this->events = $events;
        $this->transitions = $transitions;
    }
}
