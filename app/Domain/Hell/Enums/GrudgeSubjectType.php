<?php

namespace App\Domain\Hell\Enums;

final class GrudgeSubjectType
{
    public const DYNASTY = 'dynasty';
    public const SAINT = 'saint';
    public const MONASTERY = 'monastery';
    public const RULER = 'ruler';
    public const EXORCIST = 'exorcist';
    public const HOLY_ORDER = 'holy_order';

    public static function all(): array
    {
        return [
            self::DYNASTY,
            self::SAINT,
            self::MONASTERY,
            self::RULER,
            self::EXORCIST,
            self::HOLY_ORDER,
        ];
    }
}
