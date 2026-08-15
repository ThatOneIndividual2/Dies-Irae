<?php

namespace App\Domain\Hell\Enums;

/**
 * Event keys the calendar/handler registry may subscribe to.
 * Hell emits them. Time schedules them. Hell is not a second clock.
 */
final class HellEventType
{
    public const INCURSION_PULSE = 'hell_incursion_pulse';
    public const TERRITORY_DESPAIR_TICK = 'territory_despair_tick';
    public const MANIFESTATION = 'hell_manifestation';
    public const CULT_REVEALED = 'cult_revealed';
    public const POSSESSION_DEEPENED = 'possession_deepened';
    public const NEIGHBOR_TEMPTED = 'hell_neighbor_tempted';
    public const HOST_EMERGED = 'demonic_host_emerged';
    public const HOST_COLLAPSED = 'demonic_host_collapsed';
    public const BREACH_OPENED = 'infernal_breach_opened';
    public const BREACH_CLOSED = 'infernal_breach_closed';
    public const STRONGHOLD_DECLARED = 'infernal_stronghold_declared';
    public const NAMED_DEMON_DESTROYED = 'named_demon_destroyed';
    public const COUNTERMEASURE_RESOLVED = 'hell_countermeasure_resolved';
    public const SETTLEMENT_CORRUPTED = 'settlement_corrupted';
    public const APOCALYPSE_STAGE_CHECK = 'apocalypse_stage_check';
}
