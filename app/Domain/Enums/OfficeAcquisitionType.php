<?php

namespace App\Domain\Enums;

final class OfficeAcquisitionType
{
    public const APPOINTMENT = 'appointment';
    public const ELECTION = 'election';
    public const CONSECRATION = 'consecration';
    public const PAPAL_PROVISION = 'papal_provision';
    public const LOCAL_INVESTITURE = 'local_investiture';
    public const USURPATION = 'usurpation';
    public const VACANCY_FILL = 'vacancy_fill';

    public static function all(): array
    {
        return [
            self::APPOINTMENT,
            self::ELECTION,
            self::CONSECRATION,
            self::PAPAL_PROVISION,
            self::LOCAL_INVESTITURE,
            self::USURPATION,
            self::VACANCY_FILL,
        ];
    }
}
