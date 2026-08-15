<?php

namespace App\Domain\Enums;

final class ArmyKind
{
    public const LEVY = 'levy';
    public const HOSTILE = 'hostile';
    public const DEMONIC = 'demonic';

    public static function all(): array
    {
        return [self::LEVY, self::HOSTILE, self::DEMONIC];
    }
}
