<?php

namespace App\Domain\Enums;

final class ExtraordinaryElectionRule
{
    public const RELOCATE_SEAT = 'relocate_seat';
    public const REDUCED_COLLEGE = 'reduced_college';
    public const EXPAND_TO_BISHOPS = 'expand_to_bishops';
    public const DELAYED_ASSEMBLY = 'delayed_assembly';
    public const SIMPLE_MAJORITY = 'simple_majority';
    public const IMPERIAL_APPOINTMENT = 'imperial_appointment';
    public const SEDE_VACANTE_PERSISTS = 'sede_vacante_persists';

    public static function all(): array
    {
        return [
            self::RELOCATE_SEAT,
            self::REDUCED_COLLEGE,
            self::EXPAND_TO_BISHOPS,
            self::DELAYED_ASSEMBLY,
            self::SIMPLE_MAJORITY,
            self::IMPERIAL_APPOINTMENT,
            self::SEDE_VACANTE_PERSISTS,
        ];
    }
}
