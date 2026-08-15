<?php

namespace App\Domain\Enums;

final class ReligiousState
{
    public const NONE = 'none';
    public const SECULAR_CLERIC = 'secular_cleric';
    public const REGULAR = 'regular';
    public const LAY_BROTHER = 'lay_brother';
    public const NUN = 'nun';
    public const LAICIZED = 'laicized';

    public static function all(): array
    {
        return [
            self::NONE,
            self::SECULAR_CLERIC,
            self::REGULAR,
            self::LAY_BROTHER,
            self::NUN,
            self::LAICIZED,
        ];
    }
}
