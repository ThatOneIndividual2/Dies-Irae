<?php

namespace App\Domain\Enums;

final class SpiritualVisibilityClass
{
    public const PRIVATE = 'private';
    public const INFERABLE = 'inferable';
    public const EVENT = 'event';
    public const CLERGY = 'clergy';
    public const SEALED = 'sealed';
    public const PUBLIC = 'public';
    public const ADMIN = 'admin';

    public static function all(): array
    {
        return [
            self::PRIVATE,
            self::INFERABLE,
            self::EVENT,
            self::CLERGY,
            self::SEALED,
            self::PUBLIC,
            self::ADMIN,
        ];
    }
}
