<?php

namespace App\Domain\Enums;

final class FractureKind
{
    public const DOCTRINAL_HERESY = 'doctrinal_heresy';
    public const SCHISM = 'schism';
    public const APOSTASY = 'apostasy';
    public const POPULAR_MOVEMENT = 'popular_movement';
    public const CLANDESTINE_CULT = 'clandestine_cult';
    public const DEMONIC_CULT = 'demonic_cult';
    public const ANTI_CLERICAL_UNREST = 'anti_clerical_unrest';
    public const FALSE_PROPHET_MOVEMENT = 'false_prophet_movement';

    public static function all(): array
    {
        return [
            self::DOCTRINAL_HERESY,
            self::SCHISM,
            self::APOSTASY,
            self::POPULAR_MOVEMENT,
            self::CLANDESTINE_CULT,
            self::DEMONIC_CULT,
            self::ANTI_CLERICAL_UNREST,
            self::FALSE_PROPHET_MOVEMENT,
        ];
    }
}
