<?php

namespace App\Domain\Enums;

final class SpiritualOfficeKey
{
    public const PARISH_PRIEST = 'parish_priest';
    public const ABBOT = 'abbot';
    public const BISHOP = 'bishop';
    public const POPE = 'pope';

    public static function all(): array
    {
        return [self::PARISH_PRIEST, self::ABBOT, self::BISHOP, self::POPE];
    }
}
