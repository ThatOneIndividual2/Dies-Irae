<?php

namespace App\Domain\Population\Ports;

interface ClergyVacancyPort
{
    public function openVacancies(int $worldId, string $settlementId, int $vacancies, string $cause): void;
}
