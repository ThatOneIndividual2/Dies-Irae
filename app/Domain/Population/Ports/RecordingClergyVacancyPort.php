<?php

namespace App\Domain\Population\Ports;

final class RecordingClergyVacancyPort implements ClergyVacancyPort
{
    /** @var list<array<string,mixed>> */
    public array $events = [];

    public function openVacancies(int $worldId, string $settlementId, int $vacancies, string $cause): void
    {
        $this->events[] = [
            'world_id' => $worldId,
            'settlement_id' => $settlementId,
            'vacancies' => $vacancies,
            'cause' => $cause,
        ];
    }
}
