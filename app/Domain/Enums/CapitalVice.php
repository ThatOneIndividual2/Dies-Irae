<?php

namespace App\Domain\Enums;

final class CapitalVice
{
    public const PRIDE = 'pride';
    public const GREED = 'greed';
    public const LUST = 'lust';
    public const ENVY = 'envy';
    public const GLUTTONY = 'gluttony';
    public const WRATH = 'wrath';
    public const SLOTH = 'sloth';

    public static function all(): array
    {
        return [
            self::PRIDE,
            self::GREED,
            self::LUST,
            self::ENVY,
            self::GLUTTONY,
            self::WRATH,
            self::SLOTH,
        ];
    }
}
