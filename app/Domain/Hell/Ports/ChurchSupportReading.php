<?php

namespace App\Domain\Hell\Ports;

/**
 * Church supplies clergy, relics, and holy orders to countermeasure attempts.
 * Hell does not store sees or papal offices.
 */
interface ChurchSupportReading
{
    public function clergyPresence(string $territoryId): int;

    public function clergyRankWeight(string $characterId): int;

    public function relicAuthenticity(string $territoryId): int;

    public function holyOrderSupport(string $territoryId): int;
}
