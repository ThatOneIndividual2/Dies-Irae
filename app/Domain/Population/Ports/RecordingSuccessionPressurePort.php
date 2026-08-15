<?php

namespace App\Domain\Population\Ports;

final class RecordingSuccessionPressurePort implements SuccessionPressurePort
{
    /** @var list<array<string,mixed>> */
    public array $events = [];

    public function record(int $worldId, string $settlementId, int $pressure, array $context): void
    {
        $this->events[] = [
            'world_id' => $worldId,
            'settlement_id' => $settlementId,
            'pressure' => $pressure,
            'context' => $context,
        ];
    }
}
