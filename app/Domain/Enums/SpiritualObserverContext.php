<?php

namespace App\Domain\Enums;

final class SpiritualObserverContext
{
    public const SELF = 'self';
    public const PUBLIC = 'public';
    public const CLERGY = 'clergy';
    public const CONFESSOR = 'confessor';
    public const EXORCIST = 'exorcist';
    public const POLITICAL = 'political';
    public const ADMIN = 'admin';

    public static function all(): array
    {
        return [
            self::SELF,
            self::PUBLIC,
            self::CLERGY,
            self::CONFESSOR,
            self::EXORCIST,
            self::POLITICAL,
            self::ADMIN,
        ];
    }
}
