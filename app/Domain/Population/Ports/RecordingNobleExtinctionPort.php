<?php

namespace App\Domain\Population\Ports;

final class RecordingNobleExtinctionPort implements NobleExtinctionPort
{
    /** @var list<array<string,mixed>> */
    public array $events = [];

    public function recordLocalExtinction(int $worldId, string $settlementId, string $cause): void
    {
        $this->events[] = [
            'world_id' => $worldId,
            'settlement_id' => $settlementId,
            'cause' => $cause,
        ];
    }
}
