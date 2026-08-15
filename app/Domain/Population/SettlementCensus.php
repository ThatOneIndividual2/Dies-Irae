<?php

namespace App\Domain\Population;

/**
 * Point-in-time readout of a settlement. Used by inspect UI and map modes.
 */
final class SettlementCensus
{
    public static function of(Settlement $settlement): array
    {
        return $settlement->census();
    }
}
