<?php

namespace App\Domain\Population\Ports;

interface CharacterDeathPort
{
    /**
     * Named characters may die when demographic nobles/clergy collapse.
     * The population engine never requires this to fire.
     *
     * @param  array<string,mixed>  $context
     */
    public function considerDeaths(int $worldId, string $settlementId, array $context): int;
}
