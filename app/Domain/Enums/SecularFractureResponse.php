<?php

namespace App\Domain\Enums;

final class SecularFractureResponse
{
    public const TOLERATE = 'tolerate';
    public const SUPPRESS = 'suppress';
    public const EXPLOIT = 'exploit';
    public const PATRONIZE = 'patronize';
    public const NEGOTIATE = 'negotiate';
    public const EXPEL = 'expel';
    public const IMPRISON_LEADERS = 'imprison_leaders';
    public const CONFISCATE_PROPERTY = 'confiscate_property';

    public static function all(): array
    {
        return [
            self::TOLERATE,
            self::SUPPRESS,
            self::EXPLOIT,
            self::PATRONIZE,
            self::NEGOTIATE,
            self::EXPEL,
            self::IMPRISON_LEADERS,
            self::CONFISCATE_PROPERTY,
        ];
    }
}
