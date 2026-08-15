<?php

namespace App\Domain\Enums;

final class ChurchFractureResponse
{
    public const PREACHING = 'preaching';
    public const THEOLOGICAL_CONDEMNATION = 'theological_condemnation';
    public const LOCAL_SYNOD = 'local_synod';
    public const EXCOMMUNICATION = 'excommunication';
    public const INTERDICT = 'interdict';
    public const INQUISITORIAL_INVESTIGATION = 'inquisitorial_investigation';
    public const RECONCILIATION = 'reconciliation';
    public const PENANCE = 'penance';
    public const MILITARY_SUPPRESSION = 'military_suppression';
    public const PAPAL_INTERVENTION = 'papal_intervention';

    public static function all(): array
    {
        return [
            self::PREACHING,
            self::THEOLOGICAL_CONDEMNATION,
            self::LOCAL_SYNOD,
            self::EXCOMMUNICATION,
            self::INTERDICT,
            self::INQUISITORIAL_INVESTIGATION,
            self::RECONCILIATION,
            self::PENANCE,
            self::MILITARY_SUPPRESSION,
            self::PAPAL_INTERVENTION,
        ];
    }
}
