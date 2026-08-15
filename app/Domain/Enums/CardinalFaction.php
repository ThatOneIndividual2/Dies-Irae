<?php

namespace App\Domain\Enums;

final class CardinalFaction
{
    public const CURIAL = 'curial';
    public const ITALIAN = 'italian';
    public const FRENCH = 'french';
    public const IMPERIAL = 'imperial';
    public const REFORM = 'reform';
    public const NONE = 'none';

    public static function all(): array
    {
        return [
            self::CURIAL,
            self::ITALIAN,
            self::FRENCH,
            self::IMPERIAL,
            self::REFORM,
            self::NONE,
        ];
    }
}
