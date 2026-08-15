<?php

namespace App\Domain\Population\Ports;

interface SuccessionPressurePort
{
    /**
     * @param  array<string,mixed>  $context
     */
    public function record(int $worldId, string $settlementId, int $pressure, array $context): void;
}
