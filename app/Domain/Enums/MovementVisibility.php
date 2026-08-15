<?php

namespace App\Domain\Enums;

final class MovementVisibility
{
    public const SECRET = 'secret';
    public const RUMORED = 'rumored';
    public const PUBLIC = 'public';

    public static function all(): array
    {
        return [
            self::SECRET,
            self::RUMORED,
            self::PUBLIC,
        ];
    }
}
