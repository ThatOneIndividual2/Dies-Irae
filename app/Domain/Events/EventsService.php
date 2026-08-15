<?php

namespace App\Domain\Events;

use App\Actions\Events\InspectEventEligibility;
use App\Actions\Events\PulseNarrativeEvents;
use App\Models\World;

final class EventsService implements EventsContract
{
    public function __construct(
        private PulseNarrativeEvents $pulse,
        private InspectEventEligibility $inspect
    ) {
    }

    public function domainKey(): string
    {
        return 'events';
    }

    /**
     * @return array<string, mixed>
     */
    public function pulse(World $world, bool $force = false): array
    {
        return $this->pulse->execute($world, $force);
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(World $world, ?string $definitionKey = null): array
    {
        return $this->inspect->execute($world, $definitionKey);
    }
}
