<?php

namespace App\Domain\Enums;

final class DetectionSource
{
    public const RUMOR = 'rumor';
    public const INVESTIGATION = 'investigation';
    public const DENUNCIATION = 'denunciation';
    public const CONFESSION = 'confession';
    public const INFORMANT = 'informant';
    public const CLERGY_REPORT = 'clergy_report';
    public const INQUISITORIAL_INVESTIGATION = 'inquisitorial_investigation';
    public const FALSE_ACCUSATION = 'false_accusation';

    public static function all(): array
    {
        return [
            self::RUMOR,
            self::INVESTIGATION,
            self::DENUNCIATION,
            self::CONFESSION,
            self::INFORMANT,
            self::CLERGY_REPORT,
            self::INQUISITORIAL_INVESTIGATION,
            self::FALSE_ACCUSATION,
        ];
    }
}
